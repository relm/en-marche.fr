@api
Feature:
  In order to see ideas
  As a non logged-in user
  I should be able to access API Ideas Workshop

  Background:
    Given the following fixtures are loaded:
      | LoadIdeaData              |
      | LoadIdeaThreadCommentData |
      | LoadIdeaVoteData          |

  Scenario: As a non logged-in user I can see published ideas
    When I send a "GET" request to "/api/ideas.json?status=FINALIZED"
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    [
        {
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": {
                "name": "Echelle Européenne",
                "enabled": true
            },
            "needs": [],
            "author": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-04T10:00:00+01:00",
            "committee": null,
            "status": "FINALIZED",
            "withCommittee": false,
            "createdAt": "@string@.isDateTime()",
            "name": "Réduire le gaspillage",
            "slug": "reduire-le-gaspillage",
            "daysBeforeDeadline": "@integer@",
            "authorCategory": "adherent",
            "votesCount": 0,
            "contributors_count": 0,
            "comments_count": 0
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
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": {
                "name": "Echelle Européenne",
                "enabled": true
            },
            "needs": [
                {
                    "name": "Juridique",
                    "enabled": true
                }
            ],
            "author": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-01T10:00:00+01:00",
            "committee": {
                "createdAt": "@string@.isDateTime()",
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "status": "PENDING",
            "withCommittee": true,
            "createdAt": "@string@.isDateTime()",
            "name": "Faire la paix",
            "slug": "faire-la-paix",
            "daysBeforeDeadline": "@integer@",
            "authorCategory": "committee",
            "votesCount": 21,
            "contributors_count": 0,
            "comments_count": 4
        }
    ]
    """

  Scenario: As a non logged-in user I can filter ideas by name
    When I send a "GET" request to "/api/ideas.json?name=favoriser"
    Then the response status code should be 200
    And print last response
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [
        {
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": {
                "name": "Echelle Européenne",
                "enabled": true
            },
            "needs": [],
            "author": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-02T10:00:00+01:00",
            "committee": {
                "createdAt": "@string@.isDateTime()",
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "status": "DRAFT",
            "withCommittee": true,
            "createdAt": "@string@.isDateTime()",
            "name": "Favoriser l'écologie",
            "slug": "favoriser-lecologie",
            "daysBeforeDeadline": "@integer@",
            "authorCategory": "committee",
            "votesCount": 21,
            "contributors_count": 0,
            "comments_count": 0
        }
    ]
    """

  Scenario: As a non logged-in user I can filter ideas by theme
    When I send a "GET" request to "/api/ideas.json?theme.name=defense"
    Then the response status code should be 200
    And print last response
    And the response should be in JSON
    And the JSON should be equal to:
    """
    [
        {
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": {
                "name": "Echelle Européenne",
                "enabled": true
            },
            "needs": [
                {
                    "name": "Juridique",
                    "enabled": true
                }
            ],
            "author": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-01T10:00:00+01:00",
            "committee": {
                "createdAt": "@string@.isDateTime()",
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "status": "PENDING",
            "withCommittee": true,
            "createdAt": "@string@.isDateTime()",
            "name": "Faire la paix",
            "slug": "faire-la-paix",
            "daysBeforeDeadline": "@integer@",
            "authorCategory": "committee",
            "votesCount": 21,
            "contributors_count": 0,
            "comments_count": 4
        },
        {
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": {
                "name": "Echelle Européenne",
                "enabled": true
            },
            "needs": [],
            "author": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-02T10:00:00+01:00",
            "committee": {
                "createdAt": "@string@.isDateTime()",
                "name": "En Marche Paris 8",
                "slug": "en-marche-paris-8"
            },
            "status": "DRAFT",
            "withCommittee": true,
            "createdAt": "@string@.isDateTime()",
            "name": "Favoriser l'écologie",
            "slug": "favoriser-lecologie",
            "daysBeforeDeadline": "@integer@",
            "authorCategory": "committee",
            "votesCount": 21,
            "contributors_count": 0,
            "comments_count": 0
        },
        {
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": {
                "name": "Echelle Européenne",
                "enabled": true
            },
            "needs": [],
            "author": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-03T10:00:00+01:00",
            "committee": null,
            "status": "DRAFT",
            "withCommittee": false,
            "createdAt": "@string@.isDateTime()",
            "name": "Aider les gens",
            "slug": "aider-les-gens",
            "daysBeforeDeadline": "@integer@",
            "authorCategory": "adherent",
            "votesCount": 21,
            "contributors_count": 0,
            "comments_count": 0
        },
        {
            "theme": {
                "name": "Armées et défense",
                "slug": "armees-et-defense"
            },
            "category": {
                "name": "Echelle Européenne",
                "enabled": true
            },
            "needs": [],
            "author": {
                "firstName": "Jacques",
                "lastName": "Picard"
            },
            "publishedAt": "2018-12-04T10:00:00+01:00",
            "committee": null,
            "status": "FINALIZED",
            "withCommittee": false,
            "createdAt": "@string@.isDateTime()",
            "name": "Réduire le gaspillage",
            "slug": "reduire-le-gaspillage",
            "daysBeforeDeadline": "@integer@",
            "authorCategory": "adherent",
            "votesCount": 0,
            "contributors_count": 0,
            "comments_count": 0
        }
    ]
    """

  Scenario: As a non logged-in user I should have pagination enabled
    When I send a "GET" request to "/api/ideas.json?page=2"
    Then the response status code should be 200
    And print last response
    And the response should be in JSON
    And the JSON should be equal to:
    """
    []
    """

