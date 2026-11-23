import { cpSync, mkdirSync, readdirSync, rmSync } from 'node:fs';
import { join } from 'node:path';

const publicDirectory = join(process.cwd(), 'public');
const outputDirectory = join(process.cwd(), 'vercel-static');
const excludedEntries = new Set(['.htaccess', 'hot', 'index.php', 'storage']);

rmSync(outputDirectory, { force: true, recursive: true });
mkdirSync(outputDirectory, { recursive: true });

for (const entry of readdirSync(publicDirectory, { withFileTypes: true })) {
    if (excludedEntries.has(entry.name)) {
        continue;
    }

    cpSync(
        join(publicDirectory, entry.name),
        join(outputDirectory, entry.name),
        { dereference: false, recursive: true },
    );
}
