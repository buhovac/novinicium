<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Project;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Blog posts ----------
        $posts = [
            [
                'title' => 'Why Small Businesses Still Run on Paper Agendas — and What It Costs Them',
                'slug' => 'paper-agendas-hidden-cost',
                'excerpt' => 'Double bookings, missed appointments and phone tag are not habits — they are symptoms. A look at what manual scheduling really costs a small business every month.',
                'body' => "Most small service businesses — salons, garages, independent coaches — still manage appointments by phone and paper. It feels free. It is not.\n\nA double booking costs you a client's trust. A missed appointment costs you the slot and the revenue. Answering the phone mid-service costs you focus and makes the client in front of you feel second in line.\n\nOnline booking is not about technology for its own sake. It is about moving three repetitive decisions — is this slot free, does this employee work then, how long does this service take — from your head into a system that never forgets.\n\nIn our work with small businesses we consistently see the same result: reminders alone cut no-shows dramatically, and clients book outside opening hours, when calling was never an option.\n\nIf your agenda is a notebook, the switch is smaller than you think. Start by listing your services and their durations. That single list is 80% of the setup.",
                'image_path' => '/images/blog-paper-agenda.svg',
                'author' => 'Marko Buhovac',
                'published_at' => now()->subDays(21),
            ],
            [
                'title' => 'Accessible Forms in Vue 3: Labels, Errors and Announcements Done Right',
                'slug' => 'accessible-forms-vue3',
                'excerpt' => 'A contact form that only works with a mouse is a broken form. Practical patterns for labels, aria-describedby, and live error announcements in Vue 3 + Inertia.',
                'body' => "A form is the highest-stakes component on most websites: it is where a visitor becomes a client. It is also where accessibility failures concentrate.\n\nThree patterns cover most of the ground. First, every input gets a real <label> connected via for/id — placeholder text is not a label, it disappears the moment someone types. Second, validation errors are linked to their field with aria-describedby and the field is marked aria-invalid, so a screen reader hears the problem in context, not somewhere else on the page. Third, when errors appear after submit, announce them: a container with aria-live=\"polite\" reads the summary without stealing focus.\n\nThe result costs a few attributes. The alternative costs you every visitor who navigates by keyboard or screen reader — and under the European Accessibility Act, potentially more than that.",
                'image_path' => '/images/blog-accessible-forms.svg',
                'author' => 'Marko Buhovac',
                'published_at' => now()->subDays(10),
            ],
            [
                'title' => 'From UML Diagrams to a Deployed App: a Student Project That Became a Product',
                'slug' => 'uml-to-deployed-product',
                'excerpt' => 'How a university analysis-and-design assignment — use cases, sequence diagrams, class models — turned into a working booking platform for small businesses.',
                'body' => "Academic UML projects have a reputation: diagrams that never meet a compiler. Ours went the other way.\n\nThe assignment asked for a full analysis of a reservation system — use cases, Jacobson sequence diagrams, class and state diagrams. Instead of inventing an abstract system, we treated the document as a real specification: every business rule got an identifier (BR01: no double bookings, BR06: cancellation deadline), every rule later became a test or a database constraint.\n\nThe result is that the code explains itself. When the availability service rejects a slot, the exception references the exact rule from the analysis. When a new developer asks why cancellations are blocked 24 hours before an appointment, the answer is a document, not tribal knowledge.\n\nAnalysis is not paperwork before the real work. Done honestly, it is the cheapest part of the project to change — and the most expensive one to skip.",
                'image_path' => '/images/blog-uml-product.svg',
                'author' => 'Marko Buhovac',
                'published_at' => now()->subDays(3),
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(['slug' => $post['slug']], $post);
        }

        // ---------- Portfolio projects ----------
        $carnet = Project::updateOrCreate(
            ['slug' => 'carnet-booking-platform'],
            [
                'title' => 'Carnet — Online Booking for Small Businesses',
                'description' => 'A reservation platform that replaces the phone and the paper agenda: real-time availability, conflict-free scheduling, email confirmations.',
                'body' => "Carnet lets clients of a small business book appointments online while the owner manages services, employees and weekly schedules from one dashboard.\n\nThe core of the system is the availability engine: free slots are computed from service duration, per-employee weekly schedules and existing reservations, and a pessimistic lock at booking time makes double bookings impossible even under concurrent requests.\n\nBuilt with Laravel, Vue 3 and Inertia.js on a MySQL database, with queued email confirmations and a business-rules layer derived from a formal UML analysis.",
                'live_url' => 'https://demo.carnet.example',
                'sort_order' => 1,
            ],
        );
        $carnet->images()->delete();
        $carnet->images()->createMany([
            ['image_path' => '/images/carnet-booking-flow.svg', 'alt_text' => 'Carnet booking page showing a service list, a date picker and a grid of available time slots for the selected day', 'order' => 1],
            ['image_path' => '/images/carnet-admin-dashboard.svg', 'alt_text' => 'Carnet admin dashboard with counters for today\'s appointments, upcoming appointments and monthly cancellations above a chronological list', 'order' => 2],
            ['image_path' => '/images/carnet-schedule-editor.svg', 'alt_text' => 'Weekly schedule editor listing working hours per weekday for one employee, with start and end time fields', 'order' => 3],
        ]);

        $yoat = Project::updateOrCreate(
            ['slug' => 'yoat-companion'],
            [
                'title' => 'Yoat Companion — AI Chat Application',
                'description' => 'A ChatGPT-style assistant with streamed responses, conversation history, model selection and daily usage quotas.',
                'body' => "Yoat Companion is a conversational AI application built as a full product: server-sent event streaming with simultaneous database persistence, automatically titled conversation history, a model selector backed by OpenRouter, and per-user daily quotas enforced without any cron dependency.\n\nParticular care went into the streaming architecture — responses render token by token while being collected and stored, so a refresh never loses a conversation.\n\nStack: Laravel, Vue 3, Inertia.js, TailwindCSS, SQLite, deployed on European infrastructure.",
                'live_url' => 'https://github.com/buhovac/CloneGPT',
                'sort_order' => 2,
            ],
        );
        $yoat->images()->delete();
        $yoat->images()->createMany([
            ['image_path' => '/images/yoat-chat-stream.svg', 'alt_text' => 'Yoat Companion chat interface with a user question and an assistant answer streaming in, conversation list visible in the left sidebar', 'order' => 1],
            ['image_path' => '/images/yoat-model-select.svg', 'alt_text' => 'Model selection dropdown open above the message input, listing several AI models with the active one highlighted', 'order' => 2],
        ]);

        $eac = Project::updateOrCreate(
            ['slug' => 'custom-cms-theming'],
            [
                'title' => 'Custom CMS Theming & Components',
                'description' => 'Bespoke Drupal and WordPress theming: design systems translated into accessible, maintainable component libraries.',
                'body' => "Content sites live or die by their editing experience and front-end quality. This engagement covered a complete custom theme: reusable paragraph components, Twig templating integrated with Vue single-file components where interactivity was needed, and a strict accessibility pass — semantic landmarks, keyboard navigation and WCAG AA contrast throughout.\n\nThe component approach means editors compose pages from safe building blocks instead of raw HTML, and the design system stays consistent as the site grows.",
                'live_url' => null,
                'sort_order' => 3,
            ],
        );
        $eac->images()->delete();
        $eac->images()->createMany([
            ['image_path' => '/images/cms-components.svg', 'alt_text' => 'Component library page showing a styled quote paragraph, a call-to-action block and a card grid rendered with the client\'s brand colours', 'order' => 1],
            ['image_path' => '/images/cms-editor-view.svg', 'alt_text' => 'CMS editing screen where a page is composed from labelled content blocks arranged in a vertical list', 'order' => 2],
        ]);
    }
}
