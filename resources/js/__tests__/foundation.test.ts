import { describe, expect, it } from 'vitest';
import { workspaceNames } from '../types';

describe('frontend foundation', () => {
    it('keeps the approved workspace order', () => {
        expect(workspaceNames).toEqual(['Student', 'Teacher', 'Admin']);
    });
});
