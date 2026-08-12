<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TestStudentSeeder extends Seeder
{
  public function run(): void
{
    $filePath = database_path("data/Test_Student_Data.csv");

    if (!file_exists($filePath)) {
        $this->command->error("CSV file not found at: $filePath");
        return;
    }

    $csvFile = fopen($filePath, "r");
    $headers = fgetcsv($csvFile, 2000, ","); // Get the first row: school_id, first_name, etc.

    // Default password for newly-created (dummy) student accounts
    $defaultPassword = 'User12345';

    while (($row = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
        // Combine headers with row data so we can use names instead of numbers
        $data = array_combine($headers, $row);

        // --- 1. EXTRACT DATA BY COLUMN NAME ---
        $studentId  = trim($data['school_id']);
        $firstName  = trim($data['first_name']);
        $lastName   = trim($data['last_name']);
        $middleName = trim($data['middle_name']);
        $rawEmail   = trim($data['email']);
        $program    = trim($data['program']);
        $yearLevel  = (int) $data['year_level'];
        $section    = trim($data['section']);

        // Full name for the 'name' column
        $fullName = $firstName . ' ' . $lastName;

        // --- 2. EMAIL LOGIC ---
        // If the CSV has a valid email, use it. Otherwise, auto-generate one.
        if (empty($rawEmail) || $rawEmail === '-' || !filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            $fInitial = strtolower(substr($firstName, 0, 1));
            $cleanLastName = strtolower(str_replace(' ', '', $lastName));
            $idSuffix = substr(str_replace('-', '', $studentId), -5);
            $emailToSave = $cleanLastName . '.' . $fInitial . '.' . strtolower($program) . '.' . $idSuffix . '@gmail.com';
        } else {
            $emailToSave = $rawEmail;
        }

        // --- 3. DATABASE SYNC ---
        // Check if user exists to avoid re-hashing passwords / overwriting activation state
        $existingUser = User::where('school_id', $studentId)->first();

        User::updateOrCreate(
            ['school_id' => $studentId],
            [
                'first_name'        => $firstName,
                'middle_name'       => $middleName,
                'last_name'         => $lastName,
                'name'              => $fullName,
                'email'             => $emailToSave,
                'role'              => 'student',
                'is_activated'      => $existingUser ? $existingUser->is_activated : true,
                'otp_code'          => $existingUser ? $existingUser->otp_code : null,
                'otp_expires_at'    => $existingUser ? $existingUser->otp_expires_at : null,
                'temp_email'        => $existingUser ? $existingUser->temp_email : null,
                'program'           => $program,
                'year_level'        => $yearLevel,
                'section'           => $section,
                'status'            => $existingUser ? $existingUser->status : 'enrolled',
                'email_verified_at' => $existingUser ? $existingUser->email_verified_at : now(),
                // Only hash if it's a brand new student; otherwise keep existing hash
                'password'          => $existingUser ? $existingUser->password : Hash::make($defaultPassword),
            ]
        );
    }

    fclose($csvFile);
    $this->command->info('Database synced with student data successfully. ' . '50 dummy accounts use the default password: ' . $defaultPassword);
}
}