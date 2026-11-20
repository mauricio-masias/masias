import pig from './ascii-pig-console.txt?raw';

// Console rows are at least ~14px tall whatever the font size, so the font size
// only changes the width: 10px matches the Elements proportions, smaller fonts
// make the pig narrower (8px is ~500 x 690px for 105 x 49).
const PIG_STYLE = 'font: 8px/11px monospace; color: #64ffda;';
const TITLE_STYLE = 'font: 700 28px sans-serif; color: #64ffda;';
const BODY_STYLE = 'font: 11px monospace; color: #888;';

/** The text and `%c` styles printed to the browser console, kept separate so it can be tested. */
export function signatureMessage(): [string, string, string, string] {
    return [
        `%c${pig.trimEnd()}\n%cMauricio Masias%c\nFullstack PHP Developer · React, Vue.js, Next.js\nClaude AI Architecture & Agent Orchestration`,
        PIG_STYLE,
        TITLE_STYLE,
        BODY_STYLE,
    ];
}

/** Prints the site signature once to the browser console (for anyone opening DevTools). */
export function printSignature(): void {
    console.log(...signatureMessage());
}
