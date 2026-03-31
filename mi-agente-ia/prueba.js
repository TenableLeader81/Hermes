const { GoogleGenerativeAI } = require("@google/generative-ai");

// 1. VE A https://aistudio.google.com/ Y COPIA TU API KEY
const genAI = new GoogleGenerativeAI("AIzaSyAJWfXlI1qMj3e1AQoSe4gwhSlzpQuURAw");

async function test() {
    try {
        // EL TRUCO: Forzamos 'v1' para que no busque la 'v1beta' que da 404
        const model = genAI.getGenerativeModel(
            { model: "gemini-1.5-flash-latest" },
            { apiVersion: 'v1' } 
        );

        const prompt = "Hola HERMES, responde: 'Sistema funcionando'";
        
        const result = await model.generateContent(prompt);
        const response = await result.response;
        console.log("🤖 Respuesta:", response.text());

    } catch (error) {
        console.error("❌ Error detectado:", error.message);
        console.log("💡 Si sale 404, intenta cambiar el modelo a: gemini-1.5-flash-latest");
    }
}

test();