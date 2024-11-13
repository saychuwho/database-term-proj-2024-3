// app.js

// 밑에 API_URL 꼭 INDEX.html이 있는 디렉토리 주소랑 똑같아야 함.
// API_URL은 서버 주소를 뜻하는데, 서버 주소가 INDEX.HTML을 제공하는 주소랑 같아야하기 때문. 
// PUBLIC빼는 것도 안됌.  
const API_URL = 'http://localhost/OOP/public'; // Adjust this to your actual API URL
let currentUser = null;

function showappropriateView() {
    document.getElementById('loginForm').classList.add('hidden');
    document.getElementById('registrationForm').classList.add('hidden');
    
    let viewElement;
    if (currentUser.role === 'student') {
        viewElement = document.getElementById('studentView');
        viewElement.classList.remove('hidden');
        loadStudentData();
    } else {
        viewElement = document.getElementById('instructorView');
        viewElement.classList.remove('hidden');
        loadInstructorData();
    }

    // Check if logout button already exists
    let logoutButton = viewElement.querySelector('.logout-button');
    if (!logoutButton) {
        // If it doesn't exist, create and add it
        logoutButton = document.createElement('button');
        logoutButton.textContent = 'Logout';
        logoutButton.className = 'logout-button';
        logoutButton.onclick = logout;
        viewElement.prepend(logoutButton);
    }
}

function showLoginForm() {
    document.getElementById('loginForm').classList.remove('hidden');
    document.getElementById('registrationForm').classList.remove('hidden');
    document.getElementById('studentView').classList.add('hidden');
    document.getElementById('instructorView').classList.add('hidden');
}

async function login() {
    // 사용자가 입력한 값 가져오기
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;

    try {
        const response = await fetch(`${API_URL}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ username, password }),
        });

        const data = await response.json();

        if (response.ok) {
            // html 보이는거 바꾸기 
            currentUser = data.user;
            alert('Logged in successfully');
            showappropriateView();
        } else {
            alert(`Login failed: ${data.message}`);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

async function logout() {
    try {
        const response = await fetch(`${API_URL}/logout.php`, {
            method: 'POST',
        });
        const data = await response.json();
        if (response.ok) {
            currentUser = null;
            // Remove logout button before showing login form
            document.querySelector('.logout-button')?.remove();
            showLoginForm();
        } else {
            alert(`Logout failed: ${data.message}`);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred during logout. Please try again.');
    }
}

async function checkSession() {
    try {
        const response = await fetch(`${API_URL}/check_session.php`);
        const data = await response.json();
        if (data.loggedIn) {
            currentUser = data.user;
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
            showappropriateView();
        } else {
            localStorage.removeItem('currentUser');
            showLoginForm();
        }
    } catch (error) {
        console.error('Error checking session:', error);
        showLoginForm();
    }
}

function toggleSecretCodeField() {
    const role = document.getElementById('regRole').value;
    const secretCodeField = document.getElementById('regSecretCode');
    secretCodeField.style.display = role === 'instructor' ? 'block' : 'none';
}

async function register() {
    // User input
    const username = document.getElementById('regUsername').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPassword').value;
    const role = document.getElementById('regRole').value;
    const secretCode = document.getElementById('regSecretCode').value;

    // Validate input
    if (!username || !email || !password || !role) {
        alert('Please fill in all fields.');
        return;
    }

    if (role === 'instructor' && !secretCode) {
        alert('Secret code is required for instructor registration.');
        return;
    }

    try {
        const response = await fetch(`${API_URL}/register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ username, email, password, role, secretCode }),
        });

        const data = await response.json();

        if (response.ok) {
            alert('Registered successfully. Please log in.');
        } else {
            alert(`Registration failed: ${data.message}`);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}


async function loadStudentData() {
    await loadAssignments();
    await loadStudentSubmissions();
}

async function loadInstructorData() {
    await loadAssignments();
    await loadSubmissionsToGrade();
    await loadAllSubmissionsForInstructor()
}

let assignments = [];

async function loadAssignments() {
    try {
        const response = await fetch(`${API_URL}/assignments.php`);
        assignments = await response.json();
        const studentAssignments = document.getElementById('studentAssignments').getElementsByTagName('tbody')[0];
        const instructorAssignments = document.getElementById('instructorAssignments').getElementsByTagName('tbody')[0];
        const assignmentSelect = document.getElementById('assignmentSelect');
        
        studentAssignments.innerHTML = '';
        instructorAssignments.innerHTML = '';
        assignmentSelect.innerHTML = '';
        
        for (let assignment of assignments) {
            // 각 과제에 대한 테스트 케이스를 불러옵니다.
            const testCasesResponse = await fetch(`${API_URL}/assignments.php?id=${assignment.id}`);
            const fullAssignment = await testCasesResponse.json();
            assignment.test_cases = fullAssignment.test_cases;

            if (currentUser.role === 'student') {
                studentAssignments.innerHTML += `
                    <tr>
                        <td>${assignment.title}</td>
                        <td>${assignment.due_date}</td>
                    </tr>`;
                assignmentSelect.innerHTML += `<option value="${assignment.id}">${assignment.title}</option>`;
            } else {
                instructorAssignments.innerHTML += ` 
                    <tr>
                        <td>${assignment.title}</td>
                        <td>${assignment.due_date}</td>
                        <td>${assignment.max_score}</td>
                    </tr>`;
            }
        }
        
        if (assignments.length > 0) {
            assignmentSelect.value = assignments[0].id;
            showAssignmentDescription();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load assignments. Please try again.');
    }
}

function showAssignmentDescription() {
    const assignmentId = document.getElementById('assignmentSelect').value;
    const descriptionElement = document.getElementById('assignmentDescription');
    const testCasesTable = document.getElementById('testCasesTable');
    const testCasesBody = document.getElementById('testCasesBody');
    
    console.log("hii");
    
    if (assignmentId) {
        const selectedAssignment = assignments.find(a => a.id == assignmentId);
        if (selectedAssignment) {
            descriptionElement.innerHTML = `
                <h4>Description:</h4>
                <p>${nl2br(selectedAssignment.description)}</p>
                <br>
                <h4>Due Date:</h4>
                <p> ${selectedAssignment.due_date}</p>
                <br>
                <h4>Max Score:</h4>
                <p> ${selectedAssignment.max_score}</p>
                <br>
            `;

            if (selectedAssignment.test_cases && selectedAssignment.test_cases.length > 0) {
                testCasesBody.innerHTML = selectedAssignment.test_cases.map(tc => `
                    <tr>
                        <td>${nl2br(escapeHtml(tc.input))}</td>
                        <td>${nl2br(escapeHtml(tc.expected_output))}</td>
                    </tr>
                `).join('');
                testCasesTable.classList.remove('hidden');
            } else {
                testCasesTable.classList.add('hidden');
            }
        }
    } else {
        descriptionElement.innerHTML = '';
        testCasesTable.classList.add('hidden');
    }
}

// 줄바꿈을 <br> 태그로 변환하는 함수
function nl2br(str) {
    return str.replace(/\n/g, '<br>');
}

// HTML 이스케이프 함수 (XSS 방지)
function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

// HTML 이스케이프 함수 (XSS 방지)
function escapeHtml(unsafe) {
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

async function submitAssignment() {
    const assignmentId = document.getElementById('assignmentSelect').value;
    const code = document.getElementById('codeSubmission').value;

    try {
        const response = await fetch(`${API_URL}/submissions.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ assignment_id: assignmentId, code }),
        });

        const data = await response.json();

        if (response.ok) {
            alert('Assignment submitted successfully');
            document.getElementById('codeSubmission').value = '';
            loadStudentSubmissions();
        } else {
            alert(`Submission failed: ${data.message}`);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

async function loadStudentSubmissions() {
    try {
        const response = await fetch(`${API_URL}/submissions.php`);
        const submissions = await response.json();

        const studentSubmissions = document.getElementById('studentSubmissions').getElementsByTagName('tbody')[0];
        studentSubmissions.innerHTML = '';

        submissions.forEach(submission => {
            studentSubmissions.innerHTML += `
                <tr>
                    <td>${submission.assignment_title}</td>
                    <td>${submission.submitted_at}</td>
                    <td>${submission.status}</td>
                    <td>${submission.score ? submission.score : 'N/A'}</td>
                    <td>${submission.feedback ? submission.feedback : 'N/A'}</td>
                </tr>`;
        });
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load submissions. Please try again.');
    }
}

async function loadAllSubmissionsForInstructor() {
    try {
        const response = await fetch(`${API_URL}/submissions.php?all=true`);
        const submissions = await response.json();

        // 과제별로 그룹화
        const groupedByAssignment = submissions.reduce((acc, submission) => {
            if (!acc[submission.assignment_title]) {
                acc[submission.assignment_title] = [];
            }
            acc[submission.assignment_title].push(submission);
            return acc;
        }, {});

        const assignmentTables = document.getElementById('assignmentTables__1');
        assignmentTables.innerHTML = '';

        // 각 그룹별로 테이블 생성
        Object.keys(groupedByAssignment).forEach(assignment => {
            const submissionsForAssignment = groupedByAssignment[assignment];

            // 테이블 헤더 생성
            let tableHTML = `
                <h4>${assignment}</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submitted At</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            // 각 제출물에 대한 테이블 행 생성
            submissionsForAssignment.forEach(submission => {
                tableHTML += `
                    <tr>
                        <td>${submission.student_name}</td>
                        <td>${submission.submitted_at}</td>
                        <td>${submission.status}</td>
                        <td>${submission.score ? submission.score : 'N/A'}</td>
                        <td>${submission.feedback ? submission.feedback : 'N/A'}</td>
                    </tr>`;
            });

            tableHTML += '</tbody></table>';
            assignmentTables.innerHTML += tableHTML; // 테이블을 assignmentTables div에 추가
        });

    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load all submissions. Please try again.');
    }
}

async function loadSubmissionsToGrade() {
    try {
        const response = await fetch(`${API_URL}/submissions.php?to_grade=true`);
        const submissions = await response.json();

        // 과제별로 그룹화
        const groupedByAssignment = submissions.reduce((acc, submission) => {
            if (!acc[submission.assignment_title]) {
                acc[submission.assignment_title] = [];
            }
            acc[submission.assignment_title].push(submission);
            return acc;
        }, {});

        const assignmentTables = document.getElementById('assignmentTables');
        assignmentTables.innerHTML = '';

        // 각 그룹별로 테이블 생성
        Object.keys(groupedByAssignment).forEach(assignment => {
            const submissionsForAssignment = groupedByAssignment[assignment];

            // 테이블 헤더 생성
            let tableHTML = `
                <h4>${assignment}</h4>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Submitted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            // 각 제출물에 대한 테이블 행 생성
            submissionsForAssignment.forEach(submission => {
                tableHTML += `
                    <tr>
                        <td>${submission.student_name}</td>
                        <td>${submission.submitted_at}</td>
                        <td><button onclick="gradeSubmission(${submission.id})">Grade</button></td>
                    </tr>`;
            });

            tableHTML += '</tbody></table>';
            assignmentTables.innerHTML += tableHTML; // 테이블을 assignmentTables div에 추가
        });

    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load submissions to grade. Please try again.');
    }
}

function showCreateAssignmentForm() {
    document.getElementById('createAssignmentForm').classList.remove('hidden');
}

async function createAssignment() {
    const title = document.getElementById('assignmentTitle').value;
    const description = document.getElementById('assignmentDescription_2').value;
    const dueDate = document.getElementById('assignmentDueDate').value;
    const maxScore = document.getElementById('assignmentMaxScore').value;

    const testCases = Array.from(document.getElementsByClassName('testCase')).map(testCase => {
        return {
            input: testCase.querySelector('.testInput').value,
            expected_output: testCase.querySelector('.testOutput').value,
            is_secret: testCase.querySelector('.isSecret').checked
        };
    }).filter(testCase => testCase.input.trim() !== '' && testCase.expected_output.trim() !== '');


    try {
        const requestBody = { 
            title, 
            description, 
            due_date: dueDate, 
            max_score: maxScore,
            test_cases: testCases
        };

        const response = await fetch(`${API_URL}/assignments.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestBody),
        });

        // Log the full response for debugging
        console.log('Response:', response);

        let data;
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            data = await response.json();
        } else {
            data = await response.text();
        }

        // Log the parsed data for debugging
        console.log('Data:', data);

        if (response.ok) {
            alert('Assignment created successfully');
            document.getElementById('createAssignmentForm').classList.add('hidden');
            loadAssignments();
        } else {
            throw new Error(data.message || 'Failed to create assignment');
        }
    } catch (error) {
        console.error('Error:', error);
        alert(error.message || 'An error occurred. Please try again.');
    }
}

function addTestCase() {
    const testCases = document.getElementById('testCases');
    const newTestCase = document.createElement('div');
    newTestCase.className = 'testCase';
    newTestCase.innerHTML = `
        <textarea class="testInput" placeholder="Test Input"></textarea>
        <textarea class="testOutput" placeholder="Expected Output"></textarea>
        <label class="secret-label">
            <span>SecretTestCase</span>
            <input type="checkbox" class="isSecret">
        </label>
        <button type="button" class="remove-button" onclick="removeTestCase(this)">Remove</button>
    `;
    testCases.appendChild(newTestCase);
}

function removeTestCase(button) {
    button.parentElement.remove();
}



async function gradeSubmission(submissionId) {
    console.log('Starting grading process for submission:', submissionId);
    try {
        console.log('Sending grade request to:', `${API_URL}/grade_submission.php`);
        const response = await fetch(`${API_URL}/grade_submission.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: submissionId }),
        });

        console.log('Grade response status:', response.status);
        console.log('Grade response headers:', Object.fromEntries(response.headers));

        const responseText = await response.text();
        console.log('Raw response:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
            console.log('Parsed response data:', data);
        } catch (e) {
            console.error('Error parsing JSON:', e);
            throw new Error('Invalid JSON response from server');
        }

        if (response.ok) {
            const score = data.score;
            const feedback = data.feedback;

            console.log('Updating submission with score:', score, 'and feedback:', feedback);
            const updateResponse = await fetch(`${API_URL}/submissions.php`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: submissionId, score, feedback }),
            });

            console.log('Update response status:', updateResponse.status);

            if (updateResponse.ok) {
                alert(`Submission graded successfully. Score: ${score}`);
                loadSubmissionsToGrade();
                loadAllSubmissionsForInstructor();
            } else {
                const updateData = await updateResponse.json();
                console.error('Update response data:', updateData);
                alert('Failed to update submission grade: ' + (updateData.message || 'Unknown error'));
            }
        } else {
            alert(`Failed to grade submission: ${data.error || 'Unknown error'}`);
        }
    } catch (error) {
        console.error('Error in gradeSubmission:', error);
        alert('An error occurred: ' + error.message);
    }
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    checkSession();
});