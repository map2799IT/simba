import OpenAI from "openai";

const client = new OpenAI();

async function main() {
    try {
        const response = await client.responses.create({
            model: "gpt-5.6",
            input: "Jelaskan pengertian jaringan komputer secara singkat."
        });

        console.log("Jawaban ChatGPT:");
        console.log(response.output_text);
    } catch (error) {
        console.error("Terjadi kesalahan:");
        console.error(error.message);
    }
}

main();