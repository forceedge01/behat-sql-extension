Feature:
    In order to test out the sql extension
    As the maintainer of the extension
    I want to run a test using the extension

    Scenario:
        Given I am in debug mode
        And I have "User" where:
            | email | its.inevitable+1@hotmail.com |
            | forename | Wahab |
            | lastname | Qureshi |
        And I have:
            | table | record |
            | User | email: its.inevitable+2@hotmail.com |
            | User | email: forceedge01+3@live.com |
        And I have "User" where "email: its.inevitable+4@hotmail.com, forename: abdul, lastname: qureshi"
        And I have "User" with "email: its.inevitable+5@hotmail.com, forename: abdul, lastname: qureshi"
        And I don't have "User" where "email:its.inevitable+6@hotmail.com"
        And I don't have "User" with "email:its.inevitable+7@hotmail.com"
        And I do not have "User" where "email:its.inevitable+8@hotmail.com"
        And I don't have:
            | User | email: its.inevitable+9@hotmail.com |
            | User | email: its.inevitable+10@hotmail.com |
        And I do not have:
            | User | email: its.inevitable+11@hotmail.com |
            | User | email: its.inevitable+12@hotmail.com |
        And I do not have "User" where:
            | email | its.inevitable+13@hotmail.com |
        And I have an existing "User" with "email: its.inevitable+14@hotmail.com" where "forename: Wahab, lastname: qureshi"
        And I have existing "User" where "email: its.inevitable+15@hotmail.com"
        And I save the id as "user_id"

        Then I should have "User" with:
            | email | its.inevitable+16@hotmail.com |
        And I should have "User" with "email: its.inevitable+17@hotmail.com" in the database
        And I should not have "User" with "email: its.inevitable+18@hotmail.com" in the database
        And I should not have "User" with:
            | email | its.inevitable+17@hotmail.com |
