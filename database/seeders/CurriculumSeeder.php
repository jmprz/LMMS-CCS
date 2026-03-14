<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
{
    // First, add a prerequisite subject
    $prog1 = \App\Models\Curriculum::create([
        'subject_code' => 'CS111',
        'subject_title' => 'Computer Programming 1',
        'year_level' => 1,
        'semester' => 1,
        'syllabus_topics' => 'Variables, Loops, Functions'
    ]);

    // Now add a subject that depends on it
    \App\Models\Curriculum::create([
        'subject_code' => 'CS121',
        'subject_title' => 'Computer Programming 2',
        'year_level' => 1,
        'semester' => 2,
        'prerequisite_id' => $prog1->id, // Links to Prog 1
        'syllabus_topics' => 'Arrays, Pointers, File I/O'
    ]);

    // Add a 2nd year subject for your current level
    \App\Models\Curriculum::create([
        'subject_code' => 'CS211',
        'subject_title' => 'Data Structures and Algorithms',
        'year_level' => 2,
        'semester' => 1,
        'prerequisite_id' => $prog1->id,
        'syllabus_topics' => 'Stacks, Queues, Linked Lists, Sorting'
    ]);
}
}
