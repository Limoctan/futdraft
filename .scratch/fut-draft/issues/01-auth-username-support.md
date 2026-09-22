# 01: Auth & Username Support

**What to build:** Users can register with an email, username, and password. Users can log in with either their email or username. Fortify is customized to support dual-field authentication.

**Blocked by:** None (can start immediately).

**Status:** completed

- [ ] Add `username` column to users table (string, unique, indexed)
- [ ] Update `CreateNewUser` action to require and store username
- [ ] Update `ProfileValidationRules` with username uniqueness validation
- [ ] Configure Fortify `authenticateUsing()` to allow login with email OR username
- [ ] Update registration page to include username field
- [ ] Update login page to indicate email or username is accepted
- [ ] Feature tests: register with username, login with email, login with username, login fails with wrong credentials
