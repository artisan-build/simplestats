export function useFlags(currentProject, flagBits) {
    const currentProjectHasFlags = (flags) =>
        flags.every(flag => (flagBits[flag] & currentProject.flags));

    return {currentProjectHasFlags};
}
