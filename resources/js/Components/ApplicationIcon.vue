<template>
    <img v-if="$page.props.branding.app_mark" :src="$page.props.branding.app_logo" alt="SimpleStats" width="40" height="30">
    <svg v-else xmlns="http://www.w3.org/2000/svg" width="40" height="30" version="1.1">
        <rect x="0" y="20" width="10" height="10" fill="#716bdd"/>
        <rect x="15" y="10" width="10" height="20" fill="#716bdd"/>
        <rect x="30" y="00" width="10" height="30" fill="#716bdd"/>
    </svg>
</template>
