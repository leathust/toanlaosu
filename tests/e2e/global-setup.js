import { execFileSync } from 'node:child_process';

export default function globalSetup() {
  execFileSync('php', ['artisan', 'db:seed', '--class=UatSeeder', '--force'], { stdio: 'inherit' });
}
