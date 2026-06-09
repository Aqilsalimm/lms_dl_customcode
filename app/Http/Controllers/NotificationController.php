<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return redirect()->back();
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return redirect()->back();
    }

    public function testTrigger(Request $request)
    {
        $eventType = $request->input('event_type');
        $role = $request->input('role', 'student');

        $titles = [
            'course_enrolled' => 'Course Enrolled (Test)',
            'cancel_enrollment' => 'Cancel Enrollment (Test)',
            'assignment_graded' => 'Assignment Graded (Test)',
            'announcement_posted' => 'New Announcement Posted (Test)',
            'qa_answered' => 'Q&A Message Answered (Test)',
            'quiz_feedback' => 'Quiz Feedback Submitted (Test)',
            'removed_from_course' => 'Removed From Course (Test)',
            'application_accepted' => 'Instructor Application Accepted (Test)',
            'application_rejected' => 'Instructor Application Rejected (Test)',
            'application_received' => 'Instructor Application Received (Test)',
        ];

        $messages = [
            'course_enrolled' => 'This is a test notification for Course Enrolled event.',
            'cancel_enrollment' => 'This is a test notification for Cancel Enrollment event.',
            'assignment_graded' => 'This is a test notification for Assignment Graded event.',
            'announcement_posted' => 'This is a test notification for New Announcement Posted event.',
            'qa_answered' => 'This is a test notification for Q&A Message Answered event.',
            'quiz_feedback' => 'This is a test notification for Quiz Feedback Submitted event.',
            'removed_from_course' => 'This is a test notification for Removed From Course event.',
            'application_accepted' => 'This is a test notification for Instructor Application Accepted event.',
            'application_rejected' => 'This is a test notification for Instructor Application Rejected event.',
            'application_received' => 'This is a test notification for Instructor Application Received event.',
        ];

        $title = $titles[$eventType] ?? 'Test Notification';
        $message = $messages[$eventType] ?? 'This is a simulated test notification from your settings.';

        $request->user()->notify(new \App\Notifications\LmsNotification(
            $eventType,
            $role,
            $title,
            $message,
            '/dashboard'
        ));

        return redirect()->back()->with('success', 'Test notification triggered successfully.');
    }
}
