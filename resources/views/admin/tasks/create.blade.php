<form action="{{ route('tasks.store') }}" method="POST">
    @csrf
    <input type="text" name="title" placeholder="Task Title" required>
    <textarea name="description" placeholder="Instructions"></textarea>
    
    <label>Set Deadline:</label>
    <input type="datetime-local" name="deadline" required>
    
    <button type="submit">Post Task</button>
</form>