# React Native Student App UI Implementation Plan

This plan outlines the architecture and steps to build the frontend of the student app in React Native (Expo Router) to integrate with the recently created PHP API.

## User Review Required

> [!IMPORTANT]
> This is a large feature request involving multiple screens and authentication state management. Please review the proposed app structure below to ensure it aligns with your expectations.

## Open Questions

1. **Authentication State:** Expo Router typically handles auth using grouped routes (e.g., `(app)` for protected routes and `(auth)` for login). I will restructure the `src/app` directory to support this. Is this acceptable?
2. **Local Storage:** I will use `react-native`'s `AsyncStorage` (or expo-secure-store if added) to store the JWT token. Let me know if you prefer a specific storage package. Currently, I'll use standard Expo tools.

## Proposed Changes

### 1. Project Restructuring for Authentication
We need to protect the main app screens so only logged-in users can access them.

#### [NEW] src/app/_layout.tsx (Modified)
- Wrap the app in an `AuthProvider` context.
- Handle token loading on startup.

#### [NEW] src/context/AuthContext.tsx
- Manage login state, token storage (using `localStorage` on web or a simple polyfill, or we will add `@react-native-async-storage/async-storage` using a command if needed, but for now we can use basic state or fetch). Wait, I will use a simple Context that holds the token in memory for this prototype, or use Expo's `SecureStore` if we install it. I'll propose installing `expo-secure-store`.

#### [NEW] src/app/login.tsx
- A professional login screen matching the web portal's aesthetics (gradients, glassmorphism).
- Hits the `POST /api/v1/student/login.php` endpoint.

### 2. Main App Screens (Protected)

The `AppTabs` will be updated to include the following tabs:

#### [MODIFY] src/app/index.tsx (Dashboard)
- Fetch and display `GET /api/v1/student/dashboard.php`.
- Show "Exams Completed", "Average Score", "Available Exams" using polished metric cards.
- List recent attempts.

#### [NEW] src/app/exams.tsx (Exams List)
- Fetch and display `GET /api/v1/student/exams.php`.
- Show available exams with their duration, marks, and an action button to "Start Exam".

#### [NEW] src/app/results.tsx (Results List)
- Fetch and display `GET /api/v1/student/results.php`.
- List past attempts and their pass/fail status.

### 3. Exam Taking Flow (Nested Stack)

We will create a specific modal or full-screen stack for taking an exam to prevent accidental exits.

#### [NEW] src/app/exam/[id].tsx (Exam Runner)
- **Initialization:** Calls `POST /api/v1/student/exam_init.php` to get the `attempt_id`.
- **Fetching:** Calls `GET /api/v1/student/exam_run.php` to get questions and the timer.
- **UI:** A distraction-free UI showing one question at a time (or a scrollable list), with a sticky countdown timer.
- **Syncing:** Every time an answer is selected, call `POST /api/v1/student/exam_sync.php`.
- **Submission:** A "Submit Exam" button that calls `POST /api/v1/student/exam_submit.php` and navigates to the result summary.

### 4. API Client Utility

#### [NEW] src/utils/api.ts
- A helper function to make fetch requests, automatically inject the `Authorization: Bearer <token>` header, and handle the base URL (`http://localhost/targetexam/api/v1/student`).

## Verification Plan

### Manual Verification
1. Open the Expo app (Web or Mobile).
2. You should be redirected to the Login screen.
3. Log in with the test credentials (`student@targetexam.in` / `password123` - assuming password is set, or we can create a new user).
4. View the dashboard stats.
5. Navigate to the Exams tab and start an exam.
6. Verify the timer starts, questions render, and answers can be submitted.
7. Verify the final result is displayed after submission.
