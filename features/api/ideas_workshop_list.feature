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
    When I send a "GET" request to "/api/ideas.json?status=PUBLISHED"
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    [
        {
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-06T14:56:00+01:00",
            "committee": {
                "name": "En Marche Paris 8"
            },
            "answers": [
                {
                    "content": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce aliquet, mi condimentum venenatis vestibulum, arcu neque feugiat massa, at pharetra velit sapien et elit. Sed vitae hendrerit nulla. Vivamus consectetur magna at tincidunt maximus. Aenean dictum metus vel tellus posuere venenatis.",
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
                },
                {
                    "content": "Nam nisi nunc, ornare nec elit id, porttitor vestibulum ligula. Donec enim tellus, congue non quam at, aliquam porta ex. Curabitur at eros et ex faucibus fringilla sed vel velit.",
                    "threads": []
                }
            ],
            "name": "Faire la paix",
            "daysBeforeDeadline": "@integer@"
        }
    ]
    """

  Scenario: As a non logged-in user I can see pending ideas
    When I send a "GET" request to "/api/ideas.json?status=PENDING"
    Then the response status code should be 200
    And print last response
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [
        {
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-05T14:56:00+01:00",
            "committee": {
                "name": "En Marche Paris 8"
            },
            "answers": [],
            "name": "Favoriser l'écologie",
            "daysBeforeDeadline": "@integer@"
        },
        {
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-05T15:56:00+01:00",
            "committee": {
                "name": "En Marche Paris 8"
            },
            "answers": [],
            "name": "Aider les gens",
            "daysBeforeDeadline": @integer@
        }
    ]
    """
