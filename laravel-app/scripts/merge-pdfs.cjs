const fs = require('fs');
const { PDFDocument } = require('pdf-lib');

async function main() {
  const args = process.argv.slice(2);
  if (args.length < 3) {
    console.error('Usage: node merge-pdfs.cjs <output.pdf> <input1.pdf> <input2.pdf> [...]');
    process.exit(2);
  }

  const [outputPath, ...inputPaths] = args;
  const outDoc = await PDFDocument.create();

  for (const input of inputPaths) {
    const bytes = fs.readFileSync(input);
    const srcDoc = await PDFDocument.load(bytes, { ignoreEncryption: true });
    const pages = await outDoc.copyPages(srcDoc, srcDoc.getPageIndices());
    pages.forEach((p) => outDoc.addPage(p));
  }

  const outBytes = await outDoc.save();
  fs.writeFileSync(outputPath, outBytes);
  process.exit(0);
}

main().catch((err) => {
  console.error(err?.message || String(err));
  process.exit(1);
});
