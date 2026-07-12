<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

const form = useForm({
    name: '',
    email: '',
    message: '',
});

/* Success is tracked locally (onSuccess) and announced via role="status" —
 * intentionally independent of any shared flash props in the kit middleware. */
const sent = ref(false);
const errorSummaryRef = ref<HTMLElement | null>(null);

const errorEntries = computed(() =>
    Object.entries(form.errors).filter(([, message]) => Boolean(message)),
);

function submit() {
    sent.value = false;
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            sent.value = true;
        },
        onError: () => {
            // Move focus to the error summary so keyboard and screen reader
            // users land on the explanation, not somewhere random.
            requestAnimationFrame(() => errorSummaryRef.value?.focus());
        },
    });
}
</script>

<template>
    <Head title="Contact — Nov Inicium" />

    <PublicLayout>
        <div class="mx-auto max-w-2xl px-6 py-16">
            <h1 class="text-4xl font-bold">Contact</h1>
            <p class="mt-3 text-lg text-[var(--ni-gray-light)]">
                Tell us about your project. We reply within one business day.
            </p>

            <!-- Error summary: focusable, announced, links to fields -->
            <div
                v-if="errorEntries.length"
                ref="errorSummaryRef"
                tabindex="-1"
                role="alert"
                class="mt-8 rounded-xl border-2 border-[var(--ni-gray)] bg-[var(--ni-gray-lightest)] p-5"
            >
                <h2 class="font-semibold">There is a problem with your submission</h2>
                <ul class="mt-2 list-inside list-disc text-sm">
                    <li v-for="[field, message] in errorEntries" :key="field">
                        <a :href="`#${field}`" class="underline underline-offset-2">{{ message }}</a>
                    </li>
                </ul>
            </div>

            <!-- Success status: polite announcement, visible confirmation -->
            <p
                v-if="sent"
                role="status"
                class="mt-8 rounded-xl bg-[var(--ni-green)] p-5 font-medium text-[var(--ni-gray)]"
            >
                Thank you — your message has been sent. We will get back to you soon.
            </p>

            <form class="mt-8 space-y-6" novalidate @submit.prevent="submit">
                <div>
                    <label for="name" class="block font-medium">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        name="name"
                        autocomplete="name"
                        required
                        :aria-invalid="form.errors.name ? 'true' : undefined"
                        :aria-describedby="form.errors.name ? 'name-error' : undefined"
                        class="mt-2 w-full rounded-lg border border-[var(--ni-gray-lighter)] px-4 py-3"
                    />
                    <p v-if="form.errors.name" id="name-error" class="mt-2 text-sm font-medium">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label for="email" class="block font-medium">Email address</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        required
                        :aria-invalid="form.errors.email ? 'true' : undefined"
                        :aria-describedby="form.errors.email ? 'email-error' : undefined"
                        class="mt-2 w-full rounded-lg border border-[var(--ni-gray-lighter)] px-4 py-3"
                    />
                    <p v-if="form.errors.email" id="email-error" class="mt-2 text-sm font-medium">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="message" class="block font-medium">Message</label>
                    <p id="message-hint" class="mt-1 text-sm text-[var(--ni-gray-light)]">
                        A few sentences about what you need — at least 10 characters.
                    </p>
                    <textarea
                        id="message"
                        v-model="form.message"
                        name="message"
                        rows="6"
                        required
                        :aria-invalid="form.errors.message ? 'true' : undefined"
                        :aria-describedby="form.errors.message ? 'message-hint message-error' : 'message-hint'"
                        class="mt-2 w-full rounded-lg border border-[var(--ni-gray-lighter)] px-4 py-3"
                    />
                    <p v-if="form.errors.message" id="message-error" class="mt-2 text-sm font-medium">
                        {{ form.errors.message }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-[var(--ni-gray)] px-6 py-3 font-medium text-white disabled:opacity-60"
                >
                    <span v-if="form.processing">Sending…</span>
                    <span v-else>Send message</span>
                </button>
            </form>
        </div>
    </PublicLayout>
</template>
