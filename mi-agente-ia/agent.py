from google.adk.agents import llm_agent
from vertexai.preview.reasoning_engines import AdkApp
from google.adk.tools import agent_tool
from google.adk.tools import VertexAiSearchTool

hermes_chat_bot_vertex_ai_search_agent = llm_agent.LlmAgent(
  name='HERMES_ChatBot_vertex_ai_search_agent',
  model='gemini-2.0-flash-preview',
  description=(
      'Agent specialized in performing Vertex AI Search.'
  ),
  sub_agents=[],
  instruction='Use the VertexAISearchTool to find information using Vertex AI Search.',
  tools=[
    VertexAiSearchTool(
      data_store_id='projects/project-1cc7dea1-2656-46fb-b01/locations/global/collections//dataStores/hernes-data_1774926302332'
    )
  ],
)

root_agent = llm_agent.LlmAgent(
  name='HERMES_ChatBot',
  model='gemini-2.0-flash-preview',
  description=(
      'Ayudar a los usuarios con la información de los incidentes en el campus'
  ),
  sub_agents=[],
  instruction='\"Actúa como el Analista Técnico del Proyecto HERMES de la UTEQ. Tu función principal es explicar y desglosar la documentación que tienes en tu base de conocimientos.\n\nTu metodología de respuesta:\n\nIdentificación de Origen: Antes de responder, identifica de qué documento proviene la información (ej. \'Según el Manual de Usuario...\', \'Basado en el Análisis de Riesgos...\').\n\nContextualización: Si el usuario pregunta de forma general, haz un resumen de los documentos disponibles (ej. \'Cuento con documentación sobre la arquitectura del ESP32, el plan de costos y los protocolos de red\').\n\nEstructura Técnica: Cuando expliques procesos, usa listas numeradas o viñetas para que la lectura sea clara para un estudiante o profesor de TI.\n\nReglas de Oro:\n\nProhibido Alucinar: Si el usuario pregunta por un documento que NO está en la carpeta subida, dile: \'Ese documento no forma parte de mi base de conocimientos actual\'.\n\nClaridad en Siglas: Si mencionas términos como ESP32, MQTT, o API, asegúrate de que la explicación sea coherente con el contexto del proyecto HERMES.\n\nCruce de Información: Si una pregunta involucra dos documentos (ej. costos y hardware), relaciónalos de forma lógica (ej. \'El sensor X descrito en el manual de hardware tiene un costo unitario de Y según la tabla de presupuestos\').\n\nTono: Profesional, académico y preciso. Responde siempre en español de México.\"',
  tools=[
    agent_tool.AgentTool(agent=hermes_chat_bot_vertex_ai_search_agent)
  ],
)

app = AdkApp(
    agent=root_agent,
)
