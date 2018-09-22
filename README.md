# Human Image Search
This tool uses data from social networks to get the faces of humans by their full name.

## Usage
0. Make sure that you have installed Python 3.5 and PHP 7.2
1. Install all requirements of `backend/FaceDetector.py`
2. Start a local server `php -S localhost:8000`
3. [OPTIONAL] Run `cd backend && php crawler.php <name>` to start getting the face of every instagram user and saving the url to the database
4. Visit `localhost:8000/frontend/`, enter the desired name and depth, press start and wait (depending on your depth, this can take several minutes).
5. Finished!