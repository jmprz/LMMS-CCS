<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Register New Student</h2></x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto bg-white p-8 rounded shadow">
           @if ($errors->any())
    <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif  
        <form action="{{ route('admin.students.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label>Full Name</label>
                    <input type="text" name="name" class="w-full border-gray-300 rounded" required>
                </div>
                <div class="mb-4">
                    <label>Email (or Student ID email)</label>
                    <input type="email" name="email" class="w-full border-gray-300 rounded" required>
                </div>
                <div class="mb-4">
                    <label>Year Level</label>
                    <select name="year_level" class="w-full border-gray-300 rounded">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label>Temporary Password</label>
                    <input type="password" name="password" class="w-full border-gray-300 rounded" required>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Create Account</button>
            </form>
        </div>
    </div>
</x-app-layout>