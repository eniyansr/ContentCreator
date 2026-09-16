const baseUrl = 'http://localhost:8787';

async function test() {
    const email = 'test' + Math.floor(Math.random() * 1000) + '@example.com';
    const password = 'password123';
    
    console.log('1. Registering...', email);
    let res = await fetch(`${baseUrl}/api/auth/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ full_name: 'Test', username: 'test'+Math.floor(Math.random() * 1000), email, password })
    });
    console.log('Reg:', res.status, await res.text());

    console.log('2. Logging in...');
    res = await fetch(`${baseUrl}/api/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const loginData = await res.json();
    console.log('Login:', loginData);
    
    if (loginData.token) {
        console.log('3. Fetching Dashboard...');
        res = await fetch(`${baseUrl}/api/creator/dashboard`, {
            headers: { 'Authorization': `Bearer ${loginData.token}` }
        });
        console.log('Dashboard status:', res.status);
        console.log('Dashboard response:', await res.text());
    }
}
test();
