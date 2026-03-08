<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        BlogPost::factory()->count(10)->create();
        BlogPost::factory()->count(2)->draft()->create();
    }
}
