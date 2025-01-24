<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import CodeWrapper from "@/Components/CodeWrapper.vue";
import BigStepBullet from "@/Pages/Home/Partials/Integration/BigStepBullet.vue";
import BigStepText from "@/Pages/Home/Partials/Integration/BigStepText.vue";
import SmallStepBullet from "@/Pages/Home/Partials/Integration/SmallStepBullet.vue";
import SmallStepText from "@/Pages/Home/Partials/Integration/SmallStepText.vue";
import StepText from "@/Pages/Home/Partials/Integration/StepText.vue";
import GridPlaceholder from "@/Pages/Home/Partials/Integration/GridPlaceholder.vue";
import snippetInstallLaravel from "@/Pages/Home/Partials/Integration/Snippets/install_laravel.txt?raw";
import snippetInstallPackage from "@/Pages/Home/Partials/Integration/Snippets/install_package.txt?raw";
import snippetSetupEnv from "@/Pages/Home/Partials/Integration/Snippets/setup_env.txt?raw";
import snippetSetupConfig from "@/Pages/Home/Partials/Integration/Snippets/setup_config.txt?raw";
import snippetTrackUser from "@/Pages/Home/Partials/Integration/Snippets/track_user.txt?raw";
import snippetTrackPayment from "@/Pages/Home/Partials/Integration/Snippets/track_payment.txt?raw";

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <section class="flex justify-center px-4">
        <div class="w-full max-w-screen-xl">
            <SectionHeader id="integration">
                Powerful analytics with&nbsp;<span class="text-indigo-500">zero effort</span>
            </SectionHeader>

            <div class="flex mx-auto -mt-20 xl:-mt-0 max-w-screen-md xl:max-w-screen-xl">
                <div class="hidden xl:block border-r-2 border-dotted border-indigo-400 relative left-1/2"></div>
                <div class="flex-grow grid gap-4 grid-cols-1 xl:grid-cols-[1fr_auto_1fr] items-center relative">
                    <GridPlaceholder />
                    <BigStepBullet content="1" />
                    <BigStepText :content="`Install ${appName}`" />

                    <GridPlaceholder />
                    <SmallStepBullet content="" />
                    <SmallStepText content="Install the client" />

                    <CodeWrapper lang="bash" theme="github-dark" :title="`Install ${appName}`" :code="snippetInstallPackage" />
                    <GridPlaceholder />
                    <StepText :content="`In your Laravel project, you just need to <b>install</b> the ${appName} <b>client</b>. Congrats, the tool is now already tracking your visitors!`" />

<!--                    <GridPlaceholder />-->
<!--                    <BigStepBullet content="2" />-->
<!--                    <BigStepText content="Setup" />-->

<!--                    <GridPlaceholder />-->
<!--                    <SmallStepBullet content="" />-->
<!--                    <SmallStepText content="Define your payment model" />-->

<!--                    <CodeWrapper lang="php" theme="github-dark" title="Example payment model" :code="snippetSetupConfig" />-->
<!--                    <GridPlaceholder />-->
<!--                    <StepText :content="`To track your payments, you have to add the name of your payment/transaction model in the published config.`" />-->

<!--                    <GridPlaceholder />-->
<!--                    <SmallStepBullet content="" />-->
<!--                    <SmallStepText content="Add the API key" />-->

<!--                    <CodeWrapper lang="bash" theme="github-dark" title="Add the API key" :code="snippetSetupEnv" />-->
<!--                    <GridPlaceholder />-->
<!--                    <StepText :content="`Add the API key you received after registration to your .env file to allow the client to communicate with ${appName}.`" />-->

                    <GridPlaceholder />
                    <BigStepBullet content="2" />
                    <BigStepText content="Add tracking" />

                    <GridPlaceholder />
                    <SmallStepBullet content="" />
                    <SmallStepText content="Track your users" />

                    <CodeWrapper lang="php" theme="github-dark" title="Example of the TrackablePerson contract" :code="snippetTrackUser" />
                    <GridPlaceholder />
                    <StepText :content="`To make the client <b>track</b> your user <b>registrations</b>, you only have to add the <b>TrackablePerson</b> interface and implement the contract.`" />

                    <GridPlaceholder />
                    <SmallStepBullet content="" />
                    <SmallStepText content="Track your sales" />

                    <CodeWrapper lang="php" theme="github-dark" title="Example of the TrackablePayment contract" :code="snippetTrackPayment" />
                    <GridPlaceholder />
                    <StepText :content="`To enable the client to <b>track</b> your app’s <b>payments</b>, you need to add the <b>TrackablePayment</b> interface to your payment/transaction model and implement the contract.`" />
                </div>
            </div>
            <div class="mt-10 text-center">Make sure to check out the <a class="simpleLink" href="/docs" target="_blank">documentation</a> for more details.</div>
        </div>
    </section>
</template>
