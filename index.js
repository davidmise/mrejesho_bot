// const {
//   default: makeWASocket,
//   useMultiFileAuthState,
//   DisconnectReason,
//   fetchLatestBaileysVersion
// } = require('@whiskeysockets/baileys');

// const { version } = require("react");    

// const { Boom } = require('@hapi/boom');
// const fs = require('fs');

// async function startBot() {
//   const { state, saveCreds } = await useMultiFileAuthState('auth_info');

//   const sock = makeWASocket({
//     auth: state,
//     printQRInTerminal: true
//   });

// sock.ev.on('messages.upsert', async ({ messages, type }) => {
//     if (type !== 'notify') return;

//     const msg = messages[0];
//     if (!msg.message) return;

//     const sender = msg.key.remoteJid;
//     const text = msg.message.conversation || msg.message.extendedTextMessage?.text;

//     console.log('📥 Received:', text);

//     // Auto-reply
//     await sock.sendMessage(sender, { text: 'Hello! I am a Mrejesho Bot 🤖' });
// });

//   sock.ev.on('creds.update', saveCreds);

//   sock.ev.on('connection.update', ({ connection, lastDisconnect }) => {
//     if (connection === 'close') {
//       const shouldReconnect = (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
//       console.log('connection closed due to ', lastDisconnect?.error, ', reconnecting:', shouldReconnect);
//       if (shouldReconnect) {
//         startBot();
//       }
//     } else if (connection === 'open') {
//       console.log('✅ Connected to WhatsApp');
//     }
//   });
// }

// startBot();





// version 2.1 -stable
// const {
//   default: makeWASocket,
//   useMultiFileAuthState,
//   DisconnectReason,
//   fetchLatestBaileysVersion
// } = require('@whiskeysockets/baileys');

// const { Boom } = require('@hapi/boom');
// const fs = require('fs');

// const feedbackMap = {}; // 🧠 Tracks which users are submitting feedback

// async function startBot() {
//   const { state, saveCreds } = await useMultiFileAuthState('auth_info');

//   const sock = makeWASocket({
//     auth: state,
//     printQRInTerminal: true
//   });

//   sock.ev.on('messages.upsert', async ({ messages, type }) => {
//     if (type !== 'notify') return;

//     const msg = messages[0];
//     if (!msg.message) return;

//     const sender = msg.key.remoteJid;
//     const text = msg.message.conversation || msg.message.extendedTextMessage?.text;

//     console.log(`📥 Message from ${sender}: ${text}`);

//     if (!feedbackMap[sender]) {
//       // Step 1: Greet and ask for feedback
//       feedbackMap[sender] = 'awaiting_feedback';
//       await sock.sendMessage(sender, {
//         text: 'Hello! 👋 I am Mrejesho Bot 🤖\nPlease share your feedback below:'
//       });
//     } else if (feedbackMap[sender] === 'awaiting_feedback') {
//       // Step 2: Store the feedback
//       const feedback = text;

//       // Save to file (you can also save to DB here)
//       const line = `📩 ${sender} says: ${feedback}\n`;
//       fs.appendFileSync('feedback.txt', line, 'utf8');

//       // Confirm to user
//       await sock.sendMessage(sender, {
//         text: '✅ Thank you for your feedback! 🙏'
//       });

//       // Clear their state
//       delete feedbackMap[sender];
//     }
//   });

//   sock.ev.on('creds.update', saveCreds);

//   sock.ev.on('connection.update', ({ connection, lastDisconnect }) => {
//     if (connection === 'close') {
//       const shouldReconnect =
//         (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
//       console.log('❌ Connection closed due to', lastDisconnect?.error, ', reconnecting:', shouldReconnect);
//       if (shouldReconnect) {
//         startBot();
//       }
//     } else if (connection === 'open') {
//       console.log('✅ Connected to WhatsApp');
//     }
//   });
// }

// startBot();


// version 3.0
const {
  default: makeWASocket,
  useMultiFileAuthState,
  DisconnectReason
} = require("@whiskeysockets/baileys");

const { Boom } = require("@hapi/boom");
const fs = require("fs");
const { handleFeedback } = require("./feedback"); // <-- import

async function startBot() {
  const { state, saveCreds } = await useMultiFileAuthState("auth_info");

  const sock = makeWASocket({
    auth: state,
    printQRInTerminal: true
  });

  sock.ev.on("messages.upsert", async ({ messages, type }) => {
    if (type !== "notify") return;

    const msg = messages[0];
    if (!msg.message) return;

    const sender = msg.key.remoteJid;
    const text = msg.message.conversation || msg.message.extendedTextMessage?.text;

    console.log(`📥 Message from ${sender}: ${text}`);

    // Use the feedback module
    await handleFeedback(sock, sender, text);
  });

  sock.ev.on("creds.update", saveCreds);

  sock.ev.on("connection.update", ({ connection, lastDisconnect }) => {
    if (connection === "close") {
      const shouldReconnect =
        (lastDisconnect?.error)?.output?.statusCode !== DisconnectReason.loggedOut;
      console.log("❌ Connection closed due to", lastDisconnect?.error, ", reconnecting:", shouldReconnect);
      if (shouldReconnect) {
        startBot();
      }
    } else if (connection === "open") {
      console.log("✅ Connected to WhatsApp");
    }
  });
}

startBot();
