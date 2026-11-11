# SpeakRead 🗣️📖

SpeakRead is an open-source web app that helps students improve their reading skills. Students read a passage aloud, and SpeakRead listens, detects the words they mispronounce or misread, and highlights them so they can practice and improve.

🔗 **Live demo:** [paste your Vercel link]

## Why SpeakRead?
Many students mispronounce or skip words while reading, but in a classroom a teacher can't listen to every student one by one. SpeakRead gives each student instant feedback on their reading, so they can practice on their own and get better at their own pace.

## Features
- 🎤 **Read aloud:** students read a passage using their microphone
- ❌ **Mistake detection:** finds words that were mispronounced, misread, or skipped
- 🖍️ **Highlighted feedback:** shows wrong words clearly on the passage
- 🔊 **Correct pronunciation:** listen to how the word should be spoken [check]
- 👤 **Student login:** each student has their own account
- 📈 **Progress tracking:** see improvement over time [check]

## Screenshots
![Home page](screenshots/home.png)
![Reading feedback](screenshots/feedback.png)

## How It Works
1. The student logs in and picks a passage.
2. They click **Start** and read the passage aloud.
3. Speech recognition turns their voice into text.
4. SpeakRead compares what they said with the original passage.
5. Mistakes are highlighted, and the student can practice those words again.

## Tech Stack
- Frontend: HTML, CSS, JavaScript
- Speech: Web Speech API [check]
- Backend: PHP API
- Hosting: Vercel

## Run Locally
1. Clone the repo:
   git clone https://github.com/Indhiraaps/SpeakRead_Demo.git
2. Set up your database details in the `config` folder.
3. [How to start the app, e.g. open with a local PHP server]

> Use Google Chrome for best microphone and speech support.

## Roadmap
- [ ] Support more languages (Tamil, Hindi, etc.)
- [ ] Teacher dashboard to track a whole class
- [ ] Reading levels for different ages
- [ ] Reading speed (words per minute) score

## Contributing
Issues and pull requests are welcome! Open an issue to report a bug or suggest a feature.

## License
MIT
