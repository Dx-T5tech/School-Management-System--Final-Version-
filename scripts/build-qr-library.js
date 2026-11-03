const fs = require("node:fs");
const path = require("node:path");

const sourceDirectory = path.join(__dirname, "..", "node_modules", "qrcode-generator");
const targetDirectory = path.join(__dirname, "..", "public", "assets", "js", "vendor");

fs.mkdirSync(targetDirectory, { recursive: true });
fs.copyFileSync(
    path.join(sourceDirectory, "dist", "qrcode.js"),
    path.join(targetDirectory, "qrcode-generator.js"),
);
fs.copyFileSync(
    path.join(__dirname, "..", "licenses", "qrcode-generator.MIT"),
    path.join(targetDirectory, "qrcode-generator.LICENSE"),
);
