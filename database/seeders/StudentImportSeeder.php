<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class StudentImportSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path("data/students_import.csv");

        if (!file_exists($filePath)) {
            $this->command->error("CSV file not found at: $filePath");
            return;
        }

        $csvFile = fopen($filePath, "r");

        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
            // Skip the header row
            if ($firstline) {
                $firstline = false;
                continue;
            }

            // --- 1. CLEAN RAW DATA ---
            $studentId = trim($data[0]);
            $lastName = trim($data[1]);
            $firstName = trim($data[2]);
            $middleName = trim($data[3]);

            // Full name for the 'name' column
            $fullName = $firstName . ' ' . $lastName;

            // --- 2. EMAIL VALIDATION & FALLBACK ---
            $rawEmail = isset($data[23]) ? trim($data[23]) : '';

            // Check if the email is empty, a dash, or invalid
            if (empty($rawEmail) || $rawEmail === '-' || !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {

                // Fallback: surname.initials.program@gmail.com
                $fInitial = strtolower(substr($firstName, 0, 1));
                $mInitial = !empty($middleName) ? strtolower(substr($middleName, 0, 1)) : '';
                $cleanLastName = strtolower(str_replace(' ', '', $lastName));

                // Add a small piece of the Student ID at the end to ensure it is 100% unique
                // Example: villanueva.ed.bscs.05521@gmail.com
                $idSuffix = substr(str_replace('-', '', $studentId), -5);

                $emailToSave = $cleanLastName . '.' . $fInitial . $mInitial . '.' . strtolower($program) . '.' . $idSuffix . '@gmail.com';
            } else {
                $emailToSave = $rawEmail;
            }

            // --- 3. SHORTEN PROGRAM ---
            $courseDesc = strtoupper($data[7]);
            $program = 'BSCS';
            if (Str::contains($courseDesc, 'INFORMATION TECHNOLOGY')) {
                $program = 'BSIT';
            }

            // --- 4. EXTRACT SECTION LETTER ---
            $rawSection = trim($data[9]);
            $sectionLetter = substr($rawSection, -1);

            // --- 5. CONVERT YEAR TEXT TO INTEGER ---
            $rawYear = trim($data[8]);
            $yearLevel = 0;

            if (Str::contains($rawYear, 'First')) {
                $yearLevel = 1;
            } elseif (Str::contains($rawYear, 'Second')) {
                $yearLevel = 2;
            } elseif (Str::contains($rawYear, 'Third')) {
                $yearLevel = 3;
            } elseif (Str::contains($rawYear, 'Fourth')) {
                $yearLevel = 4;
            } else {
                $yearLevel = (int) filter_var($rawYear, FILTER_SANITIZE_NUMBER_INT);
            }

            // --- 6. PASSWORD (Surname in ALL CAPS) ---
            $passwordCaps = strtoupper(str_replace(' ', '', $lastName));

            // --- 7. DATABASE SYNC ---
            User::updateOrCreate(
                ['school_id' => $studentId],
                [
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'name' => $fullName,
                    'email' => $emailToSave,
                    'role' => 'student',
                    'year_level' => $yearLevel,
                    'section' => $sectionLetter,
                    'program' => $program,
                    'password' => Hash::make($passwordCaps),
                ]
            );
        }

        fclose($csvFile);
        $this->command->info('Student database successfully imported. Invalid emails were replaced with Student ID placeholders.');
    }
}