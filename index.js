const {
  default: makeWASocket,
  useMultiFileAuthState,
  DisconnectReason
} = require("@whiskeysockets/baileys");

const qrcode = require("qrcode");
const fs = require("fs");
const path = require("path");
const { handleFeedback } = require("./feedback"); // keep this if you use feedback system

// Grab org_folder from process arguments
const orgFolder = process.argv[2] || "auth_info";
const authPath = path.join(__dirname, "auth_info", orgFolder);

async function startBot() {
  const { state, saveCreds } = await useMultiFileAuthState(authPath);

  const sock = makeWASocket({
    auth: state
  });

  sock.ev.on("messages.upsert", async ({ messages, type }) => {
    if (type !== "notify") return;

    const msg = messages[0];
    if (!msg.message) return;

    const sender = msg.key.remoteJid;
    const text = msg.message.conversation || msg.message.extendedTextMessage?.text;

    console.log(`📥 Message from ${sender}: ${text}`);

    await handleFeedback(sock, sender, text);
  });

  sock.ev.on("creds.update", saveCreds);

  sock.ev.on("connection.update", async ({ connection, lastDisconnect, qr }) => {
    if (qr) {
      // 📷 Generate QR code SVG and save it
      const qrPath = path.join(authPath, "latest_qr.svg");

      qrcode.toString(qr, { type: "svg" }, (err, svg) => {
        if (!err) {
          fs.writeFileSync(qrPath, svg);
          console.log("✅ QR code saved at:", qrPath);
        } else {
          console.error("❌ Failed to generate QR SVG:", err);
        }
      });
    }

    if (connection === "close") {
      const shouldReconnect =
        (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
      console.log("❌ Connection closed. Reconnecting?", shouldReconnect);
      if (shouldReconnect) startBot();
    }

    if (connection === "open") {
      console.log("✅ Connected to WhatsApp");
    }
  });
}

startBot();
