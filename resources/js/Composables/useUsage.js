/**
 * Check the current project's tracking usage bitmask (visitors / users /
 * payments) against the given bits, e.g. to hide columns the project does
 * not track.
 */
export function useUsage(currentProject, usageBits) {
    const currentProjectHasUsages = (bits) =>
        bits.every(bit => (usageBits[bit] & currentProject.usage));

    return {currentProjectHasUsages};
}
