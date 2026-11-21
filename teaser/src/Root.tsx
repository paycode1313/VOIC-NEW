import React from 'react';
import { Composition, Folder } from 'remotion';
import { Teaser } from './Teaser';
import timeline from './timeline.json';

// One teaser, two formats. The scenes read the canvas size and lay themselves out for each.
export const RemotionRoot: React.FC = () => (
    <Folder name="VOIC">
        <Composition id="Teaser-Web" component={Teaser} durationInFrames={timeline.durationInFrames} fps={timeline.fps} width={1920} height={1080} />
        <Composition id="Teaser-Mobile" component={Teaser} durationInFrames={timeline.durationInFrames} fps={timeline.fps} width={1080} height={1920} />
    </Folder>
);
