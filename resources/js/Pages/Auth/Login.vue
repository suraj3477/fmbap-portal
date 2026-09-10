<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Officer Log In" />

        <!-- Portal Header Banner -->
        <div class="mb-6 text-center">
            <p class="text-sm text-gray-600 mt-1 uppercase tracking-wider font-bold">
                Brahmaputra Board (FMBAP Portal)
            </p>
        </div>

        <div v-if="status" class="mb-4 text-base font-medium text-emerald-600 bg-emerald-50 p-4 rounded-lg border border-emerald-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email Address -->
            <div>
                <InputLabel for="email" value="Official Email Address" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5"
                    v-model="form.email"
                    placeholder="name@domain.gov.in"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
                <InputLabel for="password" value="Password" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5"
                    v-model="form.password"
                    placeholder="••••••••"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Remember me & Forgot Password -->
            <div class="flex items-center justify-between text-sm md:text-base">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5" />
                    <span class="ms-2 text-gray-600 font-medium">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-gray-600 hover:text-gray-900 underline font-medium"
                >
                    Forgot password?
                </Link>
            </div>

            <!-- Actions -->
            <div class="pt-2 flex items-center justify-between">
                <Link
                    :href="route('register')"
                    class="text-sm text-gray-600 hover:text-gray-900 underline font-medium"
                >
                    Need an account? Register
                </Link>

                <PrimaryButton
                    class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-3 px-6 rounded-md text-base shadow transition duration-150 ease-in-out"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Sign In
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>