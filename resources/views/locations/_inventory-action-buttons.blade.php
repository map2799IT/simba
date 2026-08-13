@php
    $inventoryButtonSize =
        ($buttonSize ?? 'sm') === 'sm'
            ? 'btn-sm'
            : '';

    $isParentLocation =
        $location->parent_id === null;

    $includeChildren =
        $includeChildren
        ?? $isParentLocation;
@endphp

<div class="d-inline-flex flex-wrap gap-1">
    <a
        href="{{ route(
            'locations.inventory.summary',
            [
                'storageLocation' =>
                    $location->getRouteKey(),

                'include_children' =>
                    $includeChildren ? 1 : 0,
            ]
        ) }}"
        class="btn {{ $inventoryButtonSize }}
            btn-outline-primary"
    >
        <i class="bi bi-list-ul me-1"></i>
        {{ $isParentLocation
            ? 'Ringkasan Induk + Turunan'
            : 'Ringkasan Lokasi' }}
    </a>

    <a
        href="{{ route(
            'locations.inventory.complete',
            [
                'storageLocation' =>
                    $location->getRouteKey(),

                'include_children' =>
                    $includeChildren ? 1 : 0,
            ]
        ) }}"
        class="btn {{ $inventoryButtonSize }}
            btn-outline-dark"
        target="_blank"
    >
        <i class="bi bi-file-earmark-text me-1"></i>
        {{ $isParentLocation
            ? 'Detail Induk + Turunan'
            : 'Detail Lokasi' }}
    </a>
</div>
