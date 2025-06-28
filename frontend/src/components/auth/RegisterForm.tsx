import React from 'react';

const RegisterForm = () => {
  return (
    <div>
      <h2>Register</h2>
      <p>Register form placeholder. This component is under development.</p>
      {/* Basic form structure to be implemented later */}
      <form>
        <div>
          <label htmlFor="name">Name</label>
          <input type="text" id="name" name="name" placeholder="Your Name" />
        </div>
        <div>
          <label htmlFor="email">Email</label>
          <input type="email" id="email" name="email" placeholder="your@email.com" />
        </div>
        <div>
          <label htmlFor="password">Password</label>
          <input type="password" id="password" name="password" placeholder="********" />
        </div>
        <div>
          <label htmlFor="password_confirmation">Confirm Password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" placeholder="********" />
        </div>
        <button type="submit">Register</button>
      </form>
    </div>
  );
};

export default RegisterForm;
