1. Define the Problem
- Problem: HR spends too much time reviewing resumes manually.
- Users: Admin, Hr and Manager
- Goal: Automatically analyze resumes and rank candidates.

2. Requirements
<h2>What the system can do.</h2>

important
- Upload resume
- Analyze resume
- Compare with job description
- Display matching score
- Generate recommendations

future functions
- Rankings
- sent email automatically

<h2>How the system should perform.</h2>

- Fast processing
- Secure data
- Responsive design
- Easy to use
- User Friendly

3. Plan the Features
Authentication
├── Login
├── Logout

Resume Module
├── Upload PDF/DOCX
├── Extract text
├── AI Analysis

Results
├── Match Score
├── Skills Found
├── Missing Skills
├── Download Report

4. Database Design
-----
Users
-----
id
name
email
password

-------
Resumes
-------
id
user_id
filename
upload_date

-------
Results
-------
id
resume_id
score
recommendation

5. Tech stack
UI/UX Design: Figma
Frontend: html, css, js
backend: js and php
Database: mysql
AI: Gemini
Storage: local storage

6. Project Structure

resume-screening-ai/

client/
    src/
    public/

server/
    controllers/
    models/
    routes/
    middleware/
    config/

README.md