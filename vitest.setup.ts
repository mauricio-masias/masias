/**
 * Supplies Web Storage to the test environment.
 *
 * Node 25 ships its own experimental localStorage global, which collides with
 * the one jsdom installs; the result is a `localStorage` property whose getter
 * returns undefined, so any browser code touching it throws. This installs a
 * small in-memory implementation instead, which is deterministic and behaves
 * the same across Node versions.
 */
class MemoryStorage implements Storage {
    private items = new Map<string, string>();

    get length(): number {
        return this.items.size;
    }

    key(index: number): string | null {
        return [...this.items.keys()][index] ?? null;
    }

    getItem(key: string): string | null {
        return this.items.has(key) ? (this.items.get(key) as string) : null;
    }

    setItem(key: string, value: string): void {
        this.items.set(String(key), String(value));
    }

    removeItem(key: string): void {
        this.items.delete(key);
    }

    clear(): void {
        this.items.clear();
    }

    [name: string]: unknown;
}

for (const name of ['localStorage', 'sessionStorage']) {
    const storage = new MemoryStorage();

    Object.defineProperty(globalThis, name, {
        configurable: true,
        writable: true,
        value: storage,
    });
}
