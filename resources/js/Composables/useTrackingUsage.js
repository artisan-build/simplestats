/**
 * Check the current project's tracking usage bitmask (visitors / users /
 * payments) against the given bits, e.g. to hide columns the project does
 * not track.
 */
export function useTrackingUsage(currentProject, trackingUsageBits) {
    const currentProjectHasTrackingUsageBits = (bits) =>
        bits.every(bit => (trackingUsageBits[bit] & currentProject.usage));

    return {currentProjectHasTrackingUsageBits};
}
