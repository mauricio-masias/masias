import { afterEach, describe, expect, it, vi } from 'vitest';
import pig from './ascii-pig-console.txt?raw';
import { printSignature, signatureMessage } from './signature';

describe('signature', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('uses one style per %c marker', () => {
        const [text, ...styles] = signatureMessage();

        expect(text.match(/%c/g)).toHaveLength(styles.length);
        expect(text).toContain('Mauricio Masias');
        expect(text).toContain('Claude AI Architecture & Agent Orchestration');
    });

    it('starts with the pig', () => {
        const [text] = signatureMessage();

        expect(text.startsWith(`%c${pig.trimEnd()}`)).toBe(true);
    });

    it('keeps the pig safe inside an HTML comment', () => {
        expect(pig).not.toMatch(/-->|<!--|--!>/);
    });

    it('prints the signature to the console once', () => {
        const log = vi.spyOn(console, 'log').mockImplementation(() => {});

        printSignature();

        expect(log).toHaveBeenCalledOnce();
        expect(log).toHaveBeenCalledWith(...signatureMessage());
    });
});
