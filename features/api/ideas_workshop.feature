@api
Feature:
  In order to see an idea
  As a non logged-in user
  I should be able to access API Ideas Workshop

  Background:
    Given the following fixtures are loaded:
      | LoadIdeaData         |
      | LoadIdeaCommentData  |

  Scenario: As a non logged-in user I can see published ideas
    When I send a "GET" request to "/api/ideas-workshop/ideas?status=published"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [
        {
            "days_before_deadline": "@integer@",
            "uuid": "e4ac3efc-b539-40ac-9417-b60df432bdc5",
            "answers": [
                {
                    "threads": [
                        {
                            "comments": [
                                {
                                    "adherent": {
                                        "firstName": "Referent",
                                        "lastName": "Referent"
                                    }
                                },
                                {
                                    "adherent": {
                                        "firstName": "Laura",
                                        "lastName": "Deloche"
                                    }
                                }
                            ]
                        }
                    ]
                }
            ],
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "published_at": "2018-12-06T14:56:00+01:00",
            "committee": {
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "name": "Faire la paix",
            "slug": "faire-la-paix"
        },
        {
            "days_before_deadline": "@integer@",
            "uuid": "aa093ce6-8b20-4d86-bfbc-91a73fe47285",
            "answers": [],
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "published_at": "2018-12-05T15:56:00+01:00",
            "committee": {
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "name": "Aider les gens",
            "slug": "aider-les-gens"
        }
    ]
    """

  Scenario: As a non logged-in user I can see pending ideas
    When I send a "GET" request to "/api/ideas-workshop/ideas?status=pending"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [
        {
            "days_before_deadline": "@integer@",
            "uuid": "3b1ea810-115f-4b2c-944d-34a55d7b7e4d",
            "answers": [],
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "published_at": "2018-12-05T14:56:00+01:00",
            "committee": {
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "name": "Favoriser l'écologie",
            "slug": "favoriser-lecologie"
        }
    ]
    """

  Scenario: As a non logged-in user I can filter published notes by name
    When I send a "GET" request to "/api/ideas-workshop/ideas?status=published&name=paix"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [
        {
            "days_before_deadline": "@integer@",
            "uuid": "e4ac3efc-b539-40ac-9417-b60df432bdc5",
            "answers": [
                {
                    "threads": [
                        {
                            "comments": [
                                {
                                    "adherent": {
                                        "firstName": "Referent",
                                        "lastName": "Referent"
                                    }
                                },
                                {
                                    "adherent": {
                                        "firstName": "Laura",
                                        "lastName": "Deloche"
                                    }
                                }
                            ]
                        }
                    ]
                }
            ],
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "published_at": "2018-12-06T14:56:00+01:00",
            "committee": {
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "name": "Faire la paix",
            "slug": "faire-la-paix"
        }
    ]
    """
