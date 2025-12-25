<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 random articles
        Article::factory()->count(20)->create();

        // Create some specific articles
        Article::factory()->active()->create([
            'title' => 'Getting Started with Laravel',
            'content' => 'Laravel is a web application framework with expressive, elegant syntax...',
            'order' => 0,
        ]);

        Article::factory()->active()->create([
            'title' => 'Advanced PHP Techniques',
            'content' => 'Learn advanced PHP programming techniques and best practices...',
            'order' => 1,
        ]);

        Article::factory()->inactive()->create([
            'title' => 'Draft: Upcoming Features',
            'content' => 'This is a draft article about upcoming features...',
            'order' => 2,
        ]);
    }
}
