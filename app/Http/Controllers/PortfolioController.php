<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Portfolio/Index', [
            'projects' => Project::query()
                ->with(['images' => fn ($q) => $q->orderBy('order')->limit(1)]) // cover only
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Project $project) => [
                    'title' => $project->title,
                    'slug' => $project->slug,
                    'description' => $project->description,
                    'cover' => $project->images->first()
                        ? [
                            'image_path' => $project->images->first()->image_url,
                            'alt_text' => $project->images->first()->alt_text,
                        ]
                        : null,
                ]),
        ]);
    }

    public function show(Project $project): Response
    {
        $project->load('images');

        return Inertia::render('Portfolio/Show', [
            'project' => [
                'title' => $project->title,
                'description' => $project->description,
                'body' => $project->body,
                'live_url' => $project->live_url,
                'images' => $project->images->map(fn ($image) => [
                    'id' => $image->id,
                    'image_path' => $image->image_url,
                    'alt_text' => $image->alt_text,
                ]),
            ],
        ]);
    }
}
