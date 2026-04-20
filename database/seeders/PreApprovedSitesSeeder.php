<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AllowedSite;

class PreApprovedSitesSeeder extends Seeder
{
    public function run(): void
    {
        $preApprovedSites = [
            // Search Engines
            ['domain' => 'google.com', 'name' => 'Google Search', 'description' => 'Search engine'],
            ['domain' => 'bing.com', 'name' => 'Bing Search', 'description' => 'Search engine'],
            
            // Educational Resources
            ['domain' => 'wikipedia.org', 'name' => 'Wikipedia', 'description' => 'Free encyclopedia'],
            ['domain' => 'w3schools.com', 'name' => 'W3Schools', 'description' => 'Web development tutorials'],
            ['domain' => 'mdn.mozilla.org', 'name' => 'MDN Web Docs', 'description' => 'Web development documentation'],
            ['domain' => 'stackoverflow.com', 'name' => 'Stack Overflow', 'description' => 'Programming Q&A'],
            ['domain' => 'github.com', 'name' => 'GitHub', 'description' => 'Code hosting platform'],
            
            // Video Learning
            ['domain' => 'youtube.com', 'name' => 'YouTube', 'description' => 'Educational videos'],
            ['domain' => 'khanacademy.org', 'name' => 'Khan Academy', 'description' => 'Free online courses'],
            ['domain' => 'coursera.org', 'name' => 'Coursera', 'description' => 'Online courses'],
            ['domain' => 'edx.org', 'name' => 'edX', 'description' => 'Online learning platform'],
            
            // Programming Resources
            ['domain' => 'codecademy.com', 'name' => 'Codecademy', 'description' => 'Interactive coding lessons'],
            ['domain' => 'freecodecamp.org', 'name' => 'freeCodeCamp', 'description' => 'Free coding curriculum'],
            ['domain' => 'geeksforgeeks.org', 'name' => 'GeeksforGeeks', 'description' => 'Computer science portal'],
            ['domain' => 'tutorialspoint.com', 'name' => 'TutorialsPoint', 'description' => 'Programming tutorials'],
            
            // Documentation
            ['domain' => 'docs.python.org', 'name' => 'Python Docs', 'description' => 'Python documentation'],
            ['domain' => 'docs.oracle.com', 'name' => 'Oracle Docs', 'description' => 'Java documentation'],
            ['domain' => 'php.net', 'name' => 'PHP Manual', 'description' => 'PHP documentation'],
            ['domain' => 'nodejs.org', 'name' => 'Node.js Docs', 'description' => 'Node.js documentation'],
            
            // Academic
            ['domain' => 'scholar.google.com', 'name' => 'Google Scholar', 'description' => 'Academic search'],
            ['domain' => 'researchgate.net', 'name' => 'ResearchGate', 'description' => 'Academic networking'],
        ];

        foreach ($preApprovedSites as $site) {
            AllowedSite::updateOrCreate(
                [
                    'domain' => $site['domain'],
                    'scope' => 'global',
                    'is_pre_approved' => true
                ],
                [
                    'name' => $site['name'],
                    'description' => $site['description'],
                    'is_pre_approved' => true,
                    'scope' => 'global'
                ]
            );
        }

        $this->command->info('✅ Pre-approved educational sites added!');
    }
}