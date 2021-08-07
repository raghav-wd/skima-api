-- Get Distinct Section with the count of students in each section ordered desc
SELECT COUNT(Regno), Section from btech_computer_science_and_engineering_ra18
GROUP BY Section
ORDER BY COUNT(Regno) DESC;

-- Selects Regno of students with max rating in the stream
SELECT Regno FROM btech_computer_science_and_engineering_ra18 
WHERE Rating = (SELECT MAX(Rating) FROM btech_computer_science_and_engineering_ra18)

--Selects max rating from each section sorting in Asc
SELECT Regno, Section, Rating FROM btech_computer_science_and_engineering_ra18 
WHERE (Section, Rating) in 
(
    SELECT Section, MAX(Rating)
    FROM btech_computer_science_and_engineering_ra18
    GROUP BY Section
)
ORDER BY Section ASC