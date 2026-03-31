const { GoogleGenerativeAI } = require("@google/generative-ai");

const genAI = new GoogleGenerativeAI("AIzaSyAJWfXlI1qMj3e1AQoSe4gwhSlzpQuURAw");

async function listar() {
    try {
        // Esto le pregunta a Google: "¿A qué modelos tengo permiso realmente?"
        const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models?key=${genAI.apiKey}`);
        const data = await response.json();
        
        console.log("--- MODELOS DISPONIBLES PARA TU LLAVE ---");
        data.models.forEach(m => {
            console.log(`-> ${m.name}`);
        });
    } catch (e) {
        console.error("Error consultando modelos:", e);
    }
}
listar();