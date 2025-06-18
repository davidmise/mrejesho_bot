// server.js
const express = require("express");
const { startBot } = require("./index");

const app = express();
const PORT = 81; // Use port 81
const HOST = "0.0.0.0"; // Allow access from external IPs

app.use(express.static("public")); // Serve HTML in 'public' folder

app.get("/start-bot", async (req, res) => {
  const org = req.query.org || "auth_info";
  console.log("🚀 Starting bot for org:", org);
  await startBot(org);
  res.send(`✅ Bot started for organization: ${org}`);
});

app.listen(PORT, HOST, () => {
  console.log(`🌐 Server running on http://158.220.102.111:${PORT}`);
});
