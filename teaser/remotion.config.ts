import { Config } from '@remotion/cli/config';

Config.setEntryPoint('src/index.ts');
Config.setVideoImageFormat('jpeg');
Config.setJpegQuality(92);
Config.setOverwriteOutput(true);
// H.264 + AAC plays everywhere: the booth laptop, Instagram, TikTok, WhatsApp.
Config.setCodec('h264');
Config.setPixelFormat('yuv420p');
