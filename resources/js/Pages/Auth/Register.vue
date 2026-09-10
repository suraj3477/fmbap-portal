<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'state_official',
    state: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Officer Registration" />

        <!-- Portal Header Banner -->
        <div class="mb-6 text-center">
            <p class="text-sm text-gray-600 mt-1 uppercase tracking-wider font-bold">
                Brahmaputra Board (FMBAP Portal)
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Full Name -->
            <div>
                <InputLabel for="name" value="Full Name" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5"
                    v-model="form.name"
                    placeholder=""
                    required
                    autofocus
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <!-- Email Address -->
            <div>
                <InputLabel for="email" value="Official Email Address" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5"
                    v-model="form.email"
                    placeholder=""
                    required
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Grid for Role and State -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Role Selector -->
                <div>
                    <InputLabel for="role" value="Designated Role" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                    <select
                        id="role"
                        v-model="form.role"
                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5 bg-white"
                        required
                    >
                        <option value="state_official">State Governments</option>
                        <option value="board_official">Brahmaputra Board (BB)</option>
                        <option value="mojs_official">Ministry of Jal Shakti (MoJS)</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.role" />
                </div>

                <!-- State Dropdown -->
                <div>
                    <InputLabel for="state" value="State / Jurisdiction" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                    <select
                        id="state"
                        v-model="form.state"
                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5 bg-white"
                        :required="form.role === 'state_official'"
                    >
                        <option value="" disabled>Select State</option>
                        <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                        <option value="Assam">Assam</option>
                        <option value="Manipur">Manipur</option>
                        <option value="Meghalaya">Meghalaya</option>
                        <option value="Mizoram">Mizoram</option>
                        <option value="Nagaland">Nagaland</option>
                        <option value="Sikkim">Sikkim</option>
                        <option value="Tripura">Tripura</option>
                        <option value="West Bengal">West Bengal</option>
                    </select>
                    <InputError class="mt-1" :message="form.errors.state" />
                </div>
            </div>

            <!-- Password -->
            <div>
                <InputLabel for="password" value="Password" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5"
                    v-model="form.password"
                    required
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div>
                <InputLabel for="password_confirmation" value="Confirm Password" class="text-sm font-bold text-gray-800 uppercase tracking-wider" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3.5"
                    v-model="form.password_confirmation"
                    required
                />
                <InputError class="mt-1" :message="form.errors.password_confirmation" />
            </div>

            <!-- Actions -->
            <div class="pt-2 flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="text-sm text-gray-600 hover:text-gray-900 underline font-medium"
                >
                    Already registered? Log in
                </Link>

                <PrimaryButton
                    class="bg-emerald-700 hover:bg-emerald-800 text-white font-semibold py-3 px-6 rounded-md text-base shadow transition duration-150 ease-in-out"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register Account
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>