const express = require("express");
const cors = require("cors");
const dotenv = require("dotenv");

// node-fetch v3 em CommonJS
const fetch = (...args) => import("node-fetch").then(({ default: fetch }) => fetch(...args));

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

app.post("/gerar-imagem", async (req, res) => {
  const { prompt } = req.body;

  if (!process.env.REPLICATE_API_TOKEN) {
    return res.status(500).json({ error: "REPLICATE_API_TOKEN não definida no .env" });
  }

  try {
    // 1. Criar a previsão
    const resposta = await fetch("https://api.replicate.com/v1/predictions", {
      method: "POST",
      headers: {
        "Authorization": `Token ${process.env.REPLICATE_API_TOKEN}`,
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        version: "stability-ai/stable-diffusion:ac732df8",
        input: { prompt }
      })

    });

    const prediction = await resposta.json();

    // 2. Polling com limite de tentativas
    let result = prediction;
    let tentativas = 0;
    const maxTentativas = 15; // ~30 segundos

    while (result.status !== "succeeded" && result.status !== "failed" && tentativas < maxTentativas) {
      await new Promise(r => setTimeout(r, 2000)); // espera 2 segundos
      const check = await fetch(`https://api.replicate.com/v1/predictions/${prediction.id}`, {
        headers: {
          "Authorization": `Token ${process.env.REPLICATE_API_TOKEN}`,
          "Content-Type": "application/json"
        }
      });
      result = await check.json();
      tentativas++;
    }

    // 3. Retornar resultado
    if (result.status === "succeeded") {
      return res.json({ imagemUrl: result.output[0] });
    } else if (result.status === "failed") {
      return res.status(500).json({ error: "Falha ao gerar imagem", details: result });
    } else {
      return res.status(504).json({ error: "Timeout", details: "A geração demorou demais" });
    }
  } catch (err) {
    console.error("Erro ao chamar Replicate:", err);
    return res.status(500).json({ error: "Erro interno no servidor", details: err.message });
  }
});

app.listen(3000, () => console.log("Servidor rodando na porta 3000"));
