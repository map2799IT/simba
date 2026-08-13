<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentAccessService;
use App\Services\StudentSpreadsheetService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentAccessService $access,
        private readonly StudentSpreadsheetService $spreadsheets
    ) {
    }

    public function index(Request $request): View
    {
        $this->access->authorizeManager($request->user());

        $query = $this->filteredQuery($request)
            ->with(['workshop', 'user'])
            ->orderBy('name')
            ->orderBy('nisn');

        return view('students.index', [
            'students' => $query
                ->paginate(20)
                ->withQueryString(),
            'workshops' => $this->access
                ->visibleWorkshops($request->user()),
            'isAdmin' => $this->access
                ->isAdmin($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->access->authorizeManager($request->user());

        return view('students.create', [
            'student' => new Student([
                'is_active' => true,
                'workshop_id' => $this->access
                    ->assignedWorkshopId($request->user()),
            ]),
            'workshops' => $this->access
                ->visibleWorkshops($request->user()),
            'isAdmin' => $this->access
                ->isAdmin($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->access->authorizeManager($request->user());

        $validated = $this->validateStudent($request);
        $validated['workshop_id'] = $this->access
            ->effectiveWorkshopId(
                $request->user(),
                $validated['workshop_id'] ?? null
            );

        $this->access->authorizeWorkshop(
            $request->user(),
            (int) $validated['workshop_id']
        );

        Student::query()->create($validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(Request $request, int $student): View
    {
        $record = $this->access->findVisibleOrFail(
            $student,
            $request->user()
        );

        return view('students.edit', [
            'student' => $record,
            'workshops' => $this->access
                ->visibleWorkshops($request->user()),
            'isAdmin' => $this->access
                ->isAdmin($request->user()),
        ]);
    }

    public function update(
        Request $request,
        int $student
    ): RedirectResponse {
        $record = $this->access->findVisibleOrFail(
            $student,
            $request->user()
        );

        $validated = $this->validateStudent(
            $request,
            $record
        );
        $validated['workshop_id'] = $this->access
            ->effectiveWorkshopId(
                $request->user(),
                $validated['workshop_id'] ?? $record->workshop_id
            );

        $this->access->authorizeWorkshop(
            $request->user(),
            (int) $validated['workshop_id']
        );

        DB::transaction(function () use ($record, $validated): void {
            $record->update($validated);

            if ($record->user_id !== null) {
                $updates = [
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?: $record->user?->email,
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('users', 'workshop_id')) {
                    $updates['workshop_id'] = $validated['workshop_id'];
                }

                DB::table('users')
                    ->where('id', $record->user_id)
                    ->update(array_filter(
                        $updates,
                        static fn (mixed $value): bool => $value !== null
                    ));
            }
        });

        return redirect()
            ->route('students.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        int $student
    ): RedirectResponse {
        $record = $this->access->findVisibleOrFail(
            $student,
            $request->user()
        );

        DB::transaction(function () use ($record): void {
            $record->update(['is_active' => false]);

            if (
                $record->user_id !== null
                && Schema::hasColumn('users', 'is_active')
            ) {
                DB::table('users')
                    ->where('id', $record->user_id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }
        });

        return redirect()
            ->route('students.index')
            ->with('success', 'Data siswa dinonaktifkan.');
    }

    public function importCreate(Request $request): View
    {
        $this->access->authorizeManager($request->user());

        return view('students.import', [
            'isAdmin' => $this->access
                ->isAdmin($request->user()),
            'workshops' => $this->access
                ->visibleWorkshops($request->user()),
        ]);
    }

    public function importStore(Request $request): RedirectResponse
    {
        $this->access->authorizeManager($request->user());

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:xlsx,csv,txt',
            ],
        ]);

        $file = $request->file('file');
        $rows = $this->spreadsheets->read(
            $file->getRealPath(),
            strtolower($file->getClientOriginalExtension())
        );

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'File tidak berisi data siswa.',
            ]);
        }

        $prepared = [];
        $errors = [];
        $seenNisn = [];
        $seenEmails = [];
        $workshops = DB::table('workshops')
            ->get(['id', 'code'])
            ->keyBy(static fn (object $workshop): string => strtoupper((string) $workshop->code));

        foreach ($rows as $row) {
            $line = (int) ($row['_row'] ?? 0);
            $nisn = preg_replace('/\D+/', '', (string) ($row['nisn'] ?? '')) ?? '';

            if (isset($seenNisn[$nisn]) && $nisn !== '') {
                $errors[] = "Baris {$line}: NISN {$nisn} duplikat di dalam file.";
                continue;
            }

            $seenNisn[$nisn] = true;

            $workshopId = $this->access
                ->assignedWorkshopId($request->user());

            if ($this->access->isAdmin($request->user())) {
                $code = strtoupper(trim((string) ($row['kode_jurusan'] ?? '')));
                $workshopId = isset($workshops[$code])
                    ? (int) $workshops[$code]->id
                    : null;
            }

            $gender = strtoupper(
                trim((string) ($row['jenis_kelamin'] ?? ''))
            );
            $gender = match ($gender) {
                'LAKI-LAKI', 'LAKI LAKI', 'MALE' => 'L',
                'PEREMPUAN', 'FEMALE' => 'P',
                default => $gender,
            };

            $data = [
                'nisn' => $nisn,
                'nis' => $this->nullableString($row['nis'] ?? null),
                'name' => trim((string) ($row['nama'] ?? '')),
                'workshop_id' => $workshopId,
                'class_name' => trim((string) ($row['kelas'] ?? '')),
                'gender' => $gender,
                'birth_date' => $this->normalizeDate($row['tanggal_lahir'] ?? null),
                'email' => $this->nullableString($row['email'] ?? null),
                'phone' => $this->nullableString($row['telepon'] ?? null),
                'school_year' => $this->nullableString($row['tahun_ajaran'] ?? null),
                'is_active' => ! in_array(
                    strtoupper(trim((string) ($row['aktif'] ?? 'YA'))),
                    ['TIDAK', 'NO', '0', 'NONAKTIF'],
                    true
                ),
            ];

            $validator = Validator::make($data, [
                'nisn' => ['required', 'regex:/^\d{10}$/'],
                'nis' => ['nullable', 'string', 'max:50'],
                'name' => ['required', 'string', 'max:150'],
                'workshop_id' => ['required', 'integer', 'exists:workshops,id'],
                'class_name' => ['required', 'string', 'max:100'],
                'gender' => ['required', Rule::in(['L', 'P'])],
                'birth_date' => ['required', 'date', 'before:today'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'school_year' => ['nullable', 'string', 'max:20'],
                'is_active' => ['boolean'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors[] = "Baris {$line}: {$message}";
                }

                continue;
            }

            $existingStudent = Student::query()
                ->where('nisn', $data['nisn'])
                ->first(['id', 'user_id', 'workshop_id']);

            if ($data['email'] !== null) {
                $normalizedEmail = strtolower($data['email']);

                if (isset($seenEmails[$normalizedEmail])) {
                    $errors[] = "Baris {$line}: email {$data['email']} duplikat di dalam file.";
                    continue;
                }

                $seenEmails[$normalizedEmail] = true;

                $studentEmailConflict = Student::query()
                    ->where('email', $data['email'])
                    ->when(
                        $existingStudent !== null,
                        fn (Builder $query) => $query->whereKeyNot($existingStudent->id)
                    )
                    ->exists();

                $userEmailConflict = DB::table('users')
                    ->where('email', $data['email'])
                    ->when(
                        $existingStudent?->user_id !== null,
                        fn ($query) => $query->where('id', '!=', $existingStudent->user_id)
                    )
                    ->exists();

                if ($studentEmailConflict || $userEmailConflict) {
                    $errors[] = "Baris {$line}: email {$data['email']} sudah digunakan.";
                    continue;
                }
            }

            if (! $this->access->isAdmin($request->user())) {
                $code = strtoupper(trim((string) ($row['kode_jurusan'] ?? '')));
                $ownCode = strtoupper((string) DB::table('workshops')
                    ->where('id', $workshopId)
                    ->value('code'));

                if ($code !== '' && $code !== $ownCode) {
                    $errors[] = "Baris {$line}: Toolman hanya dapat mengimpor siswa jurusan {$ownCode}.";
                    continue;
                }
            }

            $prepared[] = $data;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'file' => array_slice($errors, 0, 50),
            ]);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use (
            $prepared,
            $request,
            &$created,
            &$updated
        ): void {
            foreach ($prepared as $data) {
                $existing = Student::query()
                    ->where('nisn', $data['nisn'])
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    $this->access->authorizeWorkshop(
                        $request->user(),
                        (int) $existing->workshop_id
                    );

                    $existing->update($data);

                    if ($existing->user_id !== null) {
                        $userUpdates = [
                            'name' => $data['name'],
                            'updated_at' => now(),
                        ];

                        if (Schema::hasColumn('users', 'workshop_id')) {
                            $userUpdates['workshop_id'] = $data['workshop_id'];
                        }

                        if ($data['email']) {
                            $userUpdates['email'] = $data['email'];
                        }

                        DB::table('users')
                            ->where('id', $existing->user_id)
                            ->update($userUpdates);
                    }

                    $updated++;
                    continue;
                }

                Student::query()->create($data);
                $created++;
            }
        });

        return redirect()
            ->route('students.index')
            ->with(
                'success',
                "Import selesai: {$created} siswa baru dan {$updated} siswa diperbarui."
            );
    }

    public function template(Request $request): BinaryFileResponse
    {
        $this->access->authorizeManager($request->user());

        $path = resource_path(
            'templates/template-import-siswa-simba.xlsx'
        );

        abort_unless(is_file($path), 404, 'Template Excel tidak ditemukan.');

        return response()->download(
            $path,
            'template-import-siswa-simba.xlsx'
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->access->authorizeManager($request->user());

        $students = $this->filteredQuery($request)
            ->with(['workshop', 'user'])
            ->orderBy('name')
            ->get();

        $path = $this->spreadsheets->export($students);

        return response()
            ->download(
                $path,
                'data-siswa-'.now()->format('Ymd-His').'.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    public function resetPasswordEdit(
        Request $request,
        int $student
    ): View {
        $record = $this->access->findVisibleOrFail(
            $student,
            $request->user()
        );

        abort_if(
            $record->user_id === null,
            422,
            'Siswa belum melakukan registrasi akun.'
        );

        return view('students.reset-password', [
            'student' => $record->load(['workshop', 'user']),
        ]);
    }

    public function resetPasswordUpdate(
        Request $request,
        int $student
    ): RedirectResponse {
        $record = $this->access->findVisibleOrFail(
            $student,
            $request->user()
        );

        abort_if(
            $record->user_id === null,
            422,
            'Siswa belum melakukan registrasi akun.'
        );

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $updates = [
            'password' => Hash::make($validated['password']),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('users', 'must_change_password')) {
            $updates['must_change_password'] = true;
        }

        DB::table('users')
            ->where('id', $record->user_id)
            ->update($updates);

        return redirect()
            ->route('students.index')
            ->with(
                'success',
                "Password akun {$record->name} berhasil direset."
            );
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Student::query();
        $this->access->applyVisibility($query, $request->user());

        $search = trim((string) $request->input('search'));

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%");
            });
        }

        if (
            $this->access->isAdmin($request->user())
            && $request->filled('workshop_id')
        ) {
            $query->where(
                'workshop_id',
                $request->integer('workshop_id')
            );
        }

        if ($request->filled('registration_status')) {
            if ($request->input('registration_status') === 'registered') {
                $query->whereNotNull('user_id');
            }

            if ($request->input('registration_status') === 'unregistered') {
                $query->whereNull('user_id');
            }
        }

        if ($request->filled('active')) {
            $query->where(
                'is_active',
                $request->boolean('active')
            );
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStudent(
        Request $request,
        ?Student $student = null
    ): array {
        $validated = $request->validate([
            'nisn' => [
                'required',
                'regex:/^\d{10}$/',
                Rule::unique('students', 'nisn')->ignore($student?->id),
            ],
            'nis' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'workshop_id' => [
                Rule::requiredIf($this->access->isAdmin($request->user())),
                'nullable',
                'integer',
                'exists:workshops,id',
            ],
            'class_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_date' => ['required', 'date', 'before:today'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('students', 'email')->ignore($student?->id),
                Rule::unique('users', 'email')->ignore($student?->user_id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['nisn'] = preg_replace('/\D+/', '', $validated['nisn']) ?? '';
        $validated['nis'] = $this->nullableString($validated['nis'] ?? null);
        $validated['email'] = $this->nullableString($validated['email'] ?? null);
        $validated['phone'] = $this->nullableString($validated['phone'] ?? null);
        $validated['school_year'] = $this->nullableString($validated['school_year'] ?? null);
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 20000) {
            return Carbon::create(1899, 12, 30)
                ->addDays((int) floor((float) $value))
                ->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
