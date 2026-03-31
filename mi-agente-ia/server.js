const express = require('express');
const cors = require('cors');
const mysql = require('mysql2/promise');
const { GoogleGenerativeAI } = require("@google/generative-ai");

const app = express();
app.use(cors());
app.use(express.json());

// 1. CONFIGURACIÓN
// Consigue tu API KEY en https://aistudio.google.com/
const API_KEY = "AIzaSyAJWfXlI1qMj3e1AQoSe4gwhSlzpQuURAw"; 
const genAI = new GoogleGenerativeAI(API_KEY);
const model = genAI.getGenerativeModel({ 
    model: "gemini-1.5-flash-001",
    systemInstruction: "Eres HERMES, asistente de seguridad UTEQ. Usa los datos de la DB para responder brevemente."
});

const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '', 
    database: 'dbhermes'
};

// 2. CONSULTA A BASE DE DATOS
async function obtenerContextoHERMES() {
    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);
        const [tendencias] = await connection.execute(`
            SELECT categoria, COUNT(*) as total 
            FROM reportes 
            WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY categoria
        `);
        const [zonas] = await connection.execute(`
            SELECT ROUND(latitud, 3) as lat, ROUND(longitud, 3) as lon, COUNT(*) as cantidad
            FROM reportes GROUP BY lat, lon ORDER BY cantidad DESC LIMIT 3
        `);
        await connection.end();
        return {
            semanal: tendencias.map(t => `${t.categoria}: ${t.total}`).join(', ') || "Sin reportes",
            zonas: zonas.map(z => `Coord(${z.lat}, ${z.lon})`).join('; ')
        };
    } catch (error) {
        return { semanal: "Error DB", zonas: "N/A" };
    }
}

// 3. RUTA DEL CHAT
app.post('/chat', async (req, res) => {
    try {
        const { message } = req.body;
        const infoDB = await obtenerContextoHERMES();

        const prompt = `Datos actuales: ${infoDB.semanal}. Zonas: ${infoDB.zonas}. Usuario pregunta: ${message}`;
        
        const result = await model.generateContent(prompt);
        const response = await result.response;
        
        res.json({ reply: response.text() });
    } catch (error) {
        console.error(error);
        res.status(500).json({ reply: "Error en el cerebro de HERMES." });
    }
});

app.listen(3000, () => console.log('✅ Servidor HERMES en puerto 3000'));