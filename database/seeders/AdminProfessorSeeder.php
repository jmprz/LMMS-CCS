<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminProfessorSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedProfessors();
    }

    /**
     * Single fixed admin account. Not CSV-driven since there's only ever
     * one -- credentials are hardcoded here as you asked.
     */
    private function seedAdmin(): void
    {
        $existingAdmin = User::where('school_id', '12345')->first();

        User::updateOrCreate(
            ['school_id' => '12345'],
            [
                'first_name'   => 'ADMIN',
                'last_name'    => 'ADMIN',
                'name'         => 'ADMIN ADMIN',
                'email'        => 'admin@gmail.com',
                'role'         => 'admin',
                'is_activated' => 1,
                // Only hash a fresh password if this admin doesn't exist yet,
                // so re-running the seeder doesn't overwrite a changed password.
                'password'     => $existingAdmin ? $existingAdmin->password : Hash::make('LMMS@2026'),
            ]
        );

        $this->command->info('Admin account seeded (school_id: 12345).');
    }

    /**
     * Professors imported from CSV (same first_name/last_name/role columns
     * as CCS_Student_Data.csv). school_id = surname in caps, password =
     * "12345" for brand-new accounts, is_activated forced to 1 every run.
     *
     * NOTE: adjust this filename to whatever you actually place in
     * database/data/ -- the uploaded draft was named
     * "CCS_Student_Data_-_DRAFT_ADMIN.csv" even though it only contains
     * professor rows, so pick one clear name and keep it consistent with
     * your student seeder's convention.
     */
    private function seedProfessors(): void
    {
        $filePath = database_path("data/CCS_Admin_Data.csv");

        if (!file_exists($filePath)) {
            $this->command->error("Faculty CSV file not found at: $filePath");
            return;
        }

        $csvFile = fopen($filePath, "r");
        $headers = fgetcsv($csvFile, 2000, ",");

        // Tracks school_ids already assigned in THIS run, so two professors
        // sharing a surname (e.g. two "SANTOS") don't collide with each other.
        $usedSchoolIds = [];

        while (($row = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
            $data = array_combine($headers, $row);

            $firstName = trim($data['first_name']);
            $lastName  = trim($data['last_name']);

            if (empty($firstName) || empty($lastName)) {
                continue; // skip blank rows
            }

            // --- school_id: surname, all caps, spaces stripped ---
            $baseSchoolId = strtoupper(str_replace(' ', '', $lastName));
            $schoolId = $baseSchoolId;
            $suffix = 2;

            while (true) {
                $existing = User::where('school_id', $schoolId)->where('role', 'professor')->first();
                $isSameFacultyMember = $existing
                    && $existing->first_name === $firstName
                    && $existing->last_name === $lastName;
                $isTakenInThisRun = in_array($schoolId, $usedSchoolIds) && !$isSameFacultyMember;

                if ((!$existing || $isSameFacultyMember) && !$isTakenInThisRun) {
                    break; // safe to use this school_id
                }
                $schoolId = $baseSchoolId . $suffix;
                $suffix++;
            }
            $usedSchoolIds[] = $schoolId;

            $fullName = $firstName . ' ' . $lastName;
            // Placeholder email since the draft CSV has none -- swap for
            // real faculty emails once available, same as the student seeder.
            $email = strtolower(str_replace(' ', '', $lastName)) . '.'
                . strtolower(substr($firstName, 0, 1)) . '.0000faculty@gmail.com';

            $existingProfessor = User::where('school_id', $schoolId)->first();

            User::updateOrCreate(
                ['school_id' => $schoolId],
                [
                    'first_name'   => $firstName,
                    'last_name'    => $lastName,
                    'name'         => $fullName,
                    'email'        => $email,
                    'role'         => 'professor',
                    // Always force-activated per your requirement, even on re-import.
                    'is_activated' => 1,
                    // Fixed default password "12345" -- only set for brand-new
                    // accounts, so re-running this seeder doesn't reset a
                    // professor's password after they've changed it.
                    'password'     => $existingProfessor ? $existingProfessor->password : Hash::make('12345'),
                ]
            );
        }

        fclose($csvFile);
        $this->command->info('Professor accounts synced successfully.');
    }
}
