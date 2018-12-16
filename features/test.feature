Feature:
    In order to test out the sql extension
    As the maintainer of the extension
    I want to run a test using the extension

    Scenario: Test insert query.
        And I have a "User" where:
            | email | forename | lastname |
            | its.inevitable@hotmail.com  | Wahab | Qureshi |
            | forceedge01@live.com | Abdul | Qureshi |
        Then I should have a "User" with:
            | column | value |
            | email | its.inevitable@hotmail.com |
            | forename | Wahab |
            | lastname | Qureshi |
        And I should have a "User" with:
            | column | value |
            | email | forceedge01@live.com |
            | forename | Abdul |
            | lastname | Qureshi |

    Scenario: Test insert query - different syntax.
        And I have:
            | table | record |
            | User | email: its.inevitable@hotmail.com |
            | User | email: forceedge01@live.com |
        Then I should have a "User" with:
            | column | value |
            | email | its.inevitable@hotmail.com |
        And I should have a "User" with:
            | column | value |
            | email | forceedge01@live.com |
        But I should not have a "User" with:
            | column | value |
            | forename | Wahab |
        And I should not have a "User" with:
            | column | value |
            | lastname | Qureshi |

    Scenario: Test delete query.
        Given I have a "User" where:
            | email | forename | lastname |
            | its.inevitable@hotmail.com  | Wahab | Qureshi |
            | forceedge01@live.com | Abdul | Qureshi |
        And I do not have a "User" where:
            | email | lastname |
            | its.inevitable@hotmail.com | Qureshi |

        Then I should have a "User" with:
            | column | value |
            | email | forceedge01@live.com |
        But I should not have a "User" with:
            | column | value |
            | email | its.inevitable@hotmail.com |

    Scenario: Test update query.
        Given I have a "User" where:
            | email | forename | lastname |
            | its.inevitable@hotmail.com  | Wahab | Qureshi |
        And I have an existing "User" with "forename: Abdul" where "email: its.inevitable@hotmail.com"

        Then I should have a "User" with:
            | column | value |
            | forename | Abdul |
        But I should not have a "User" with:
            | column | value |
            | forename | Wahab |

    Scenario: Test substitution
        Given I have a "User" where:
            | email | forename | lastname |
            | its.inevitable@hotmail.com  | Wahab | Qureshi |
        And I save the id as "user_id"

        And I have a "User" where:
            | email | forename | lastname |
            | forceedge01@live.com | {User.forename} | {User.lastname} |

        Then I should have a "User" with:
            | column |  value |
            | email | forceedge01@live.com |
            | forename | Wahab |
            | lastname | Qureshi |

    Scenario: Test deletion queries.
        Given I have a "User" where:
            | email | forename | lastname |
            | its.inevitable@hotmail.com  | Wahab | Qureshi |
            | forceedge01@live.com  | Abdul | Qureshi |

        And I don't have:
            | table | values |
            | User | email: its.inevitable@hotmail.com, forename: Wahab |

        Then I should have a "User" with:
            | column |  value |
            | email | forceedge01@live.com |
            | forename | Abdul |
            | lastname | Qureshi |
        But I should not have a "User" with:
            | column |  value |
            | email | its.inevitable@hotmail.com |

        And I do not have:
            | table | values |
            | User | email: forceedge01@live.com, lastname: Qureshi |

        Then I should not have a "User" with:
            | column |  value |
            | email | forceedge01@live.com |
