import { defineConfig, mergeConfig } from 'vite';
import type { ConfigEnv, UserConfig } from 'vite';
import appConfig from './vite.config.ts';

// Analysis-only configuration for `make l10n-ignore`: nothing is written, but
// Vite warns if the output directory equals the project root.
export default defineConfig(async (env: ConfigEnv) => {
  const base: UserConfig = typeof appConfig === 'function' ? await appConfig(env) : await appConfig;
  return mergeConfig(base, { build: { outDir: 'build/l10n-analysis' } });
});
