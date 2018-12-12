@api
Feature:
  In order to see ideas
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
            "description": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam tincidunt sapien nec ultrices dignissim. Nunc bibendum, urna eu venenatis accumsan, quam mi tincidunt arcu, at maximus eros tortor quis risus.",
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": [],
            "needs": [
                []
            ],
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-06T14:56:00+01:00",
            "committee": {
                "createdAt": "2017-01-12T13:25:54+01:00",
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "status": "PUBLISHED",
            "createdAt": "2019-01-02T09:57:27+01:00",
            "name": "Faire la paix",
            "slug": "faire-la-paix",
            "daysBeforeDeadline": 20,
            "contributors_count": 2,
            "comments_count": 2
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
            "description": "Ut sed imperdiet sapien. Suspendisse dolor urna, hendrerit eu viverra a, venenatis nec ligula.",
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": [],
            "needs": [],
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-05T14:56:00+01:00",
            "committee": {
                "createdAt": "2017-01-12T13:25:54+01:00",
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "status": "PENDING",
            "createdAt": "2019-01-02T09:57:27+01:00",
            "name": "Favoriser l'écologie",
            "slug": "favoriser-lecologie",
            "daysBeforeDeadline": 20,
            "contributors_count": 0,
            "comments_count": 0
        },
        {
            "description": "Aliquam ligula mi, blandit a vulputate eu, maximus vel urna. Quisque at sapien a quam consequat condimentum. Vivamus vehicula tortor maximus ligula ornare,",
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": [],
            "needs": [],
            "adherent": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-05T15:56:00+01:00",
            "committee": null,
            "status": "PENDING",
            "createdAt": "2019-01-02T09:57:27+01:00",
            "name": "Aider les gens",
            "slug": "aider-les-gens",
            "daysBeforeDeadline": 20,
            "contributors_count": 0,
            "comments_count": 0
        }
    ]
    """

  Scenario: As a non logged-in user I can see ideas ordered by ascendant date
    When I send a "GET" request to "/api/ideas.json?status=PENDING"
    Then the response status code should be 200
    And print last response
    And the response should be in JSON
    And the JSON should be equal to:
