import os
from playwright.sync_api import sync_playwright, expect

def run_verification(page):
    # The PHP server is running on localhost:8000
    url = 'http://localhost:8000/index.php'
    page.goto(url, wait_until="domcontentloaded")

    # Mock data for a 16-player tournament to trigger the split view
    mock_data = {
        "torneo": { "cupo": "16", "organizador_id": 1, "estado": "en_curso" },
        "inscritos": [ {"id": i, "nombre_usuario": f"Player {i}", "foto_perfil": ""} for i in range(1, 17) ],
        "partidos": [
            # Round 1 (8 matches)
            { "id": 1, "ronda": 1, "participante1_id": 1, "participante2_id": 2 },
            { "id": 2, "ronda": 1, "participante1_id": 3, "participante2_id": 4 },
            { "id": 3, "ronda": 1, "participante1_id": 5, "participante2_id": 6 },
            { "id": 4, "ronda": 1, "participante1_id": 7, "participante2_id": 8 },
            { "id": 5, "ronda": 1, "participante1_id": 9, "participante2_id": 10 },
            { "id": 6, "ronda": 1, "participante1_id": 11, "participante2_id": 12 },
            { "id": 7, "ronda": 1, "participante1_id": 13, "participante2_id": 14 },
            { "id": 8, "ronda": 1, "participante1_id": 15, "participante2_id": 16 },
            # Round 2 (4 matches)
            { "id": 9, "ronda": 2, "fuente_partido1_id": 1, "fuente_partido2_id": 2 },
            { "id": 10, "ronda": 2, "fuente_partido1_id": 3, "fuente_partido2_id": 4 },
            { "id": 11, "ronda": 2, "fuente_partido1_id": 5, "fuente_partido2_id": 6 },
            { "id": 12, "ronda": 2, "fuente_partido1_id": 7, "fuente_partido2_id": 8 },
            # Round 3 (Semifinals)
            { "id": 13, "ronda": 3, "fuente_partido1_id": 9, "fuente_partido2_id": 10 },
            { "id": 14, "ronda": 3, "fuente_partido1_id": 11, "fuente_partido2_id": 12 },
            # Round 4 (Final)
            { "id": 15, "ronda": 4, "fuente_partido1_id": 13, "fuente_partido2_id": 14 },
        ]
    }

    # Use page.evaluate to run JavaScript in the page context
    page.evaluate("""(data) => {
        // The functions are defined inside a DOMContentLoaded listener,
        // so we need to make them available globally for the test.
        window.renderFullBracket = renderFullBracket;
        window.alignBracketConnectors = alignBracketConnectors;
        window.getRoundName = getRoundName;
        window.createMatchElement = createMatchElement;
        window.createParticipantElement = createParticipantElement;
        window.getExcelColumnName = getExcelColumnName;

        // Make the bracket container visible
        const fullBracketContainer = document.getElementById('full-bracket-container');
        fullBracketContainer.classList.remove('hidden');
        fullBracketContainer.style.display = 'block';

        // Render the bracket with our mock data
        renderFullBracket(data);
    }""", mock_data)

    # Give the alignBracketConnectors function (called via setTimeout) time to run
    page.wait_for_timeout(500)

    # Take a screenshot of the bracket container
    bracket_container = page.locator("#full-bracket-container")
    expect(bracket_container).to_be_visible()
    bracket_container.screenshot(path="jules-scratch/verification/verification.png")

    print("Screenshot saved to jules-scratch/verification/verification.png")

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        try:
            run_verification(page)
        finally:
            browser.close()

if __name__ == "__main__":
    main()
