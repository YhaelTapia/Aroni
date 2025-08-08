import os
from playwright.sync_api import sync_playwright, expect
import json

def run_verification(page):
    # The PHP server is not available, so we'll use the file protocol.
    # This won't execute PHP, but it will load the HTML and JS,
    # which is enough to test the JS rendering functions with mock data.
    file_path = os.path.abspath('index.php')
    url = f'file://{file_path}'
    page.goto(url)

    # Mock data for a 32-player tournament to test the new layout
    # This simulates the data structure returned by `get_inscritos.php`
    mock_data = {
        "success": True,
        "torneo": {
            "id": 1,
            "titulo": "Gran Torneo de 32",
            "modalidad": "1v1",
            "cupo": "32",
            "reglas": "victoria-magistral",
            "reglas_personalizadas": "",
            "organizador_id": 1,
            "estado": "en_curso"
        },
        "inscritos": [ {"id": i, "nombre_usuario": f"Player {i}", "foto_perfil": ""} for i in range(1, 33) ],
        "partidos": [
            # Round 1 (16 matches)
            { "id": i, "ronda": 1, "participante1_id": (i*2)-1, "participante2_id": i*2 } for i in range(1, 17)
        ] + [
            # Round 2 (8 matches)
            { "id": 16 + i, "ronda": 2, "fuente_partido1_id": (i*2)-1, "fuente_partido2_id": i*2 } for i in range(1, 9)
        ] + [
            # Round 3 (4 matches)
            { "id": 24 + i, "ronda": 3, "fuente_partido1_id": 16+(i*2)-1, "fuente_partido2_id": 16+i*2 } for i in range(1, 5)
        ] + [
            # Round 4 (2 matches)
            { "id": 28 + i, "ronda": 4, "fuente_partido1_id": 24+(i*2)-1, "fuente_partido2_id": 24+i*2 } for i in range(1, 3)
        ] + [
            # Round 5 (1 match)
            { "id": 31, "ronda": 5, "fuente_partido1_id": 29, "fuente_partido2_id": 30 }
        ]
    }

    # Use page.evaluate to run JavaScript in the page context
    page.evaluate("""(data) => {
        // The functions are defined inside a DOMContentLoaded listener,
        // so we need to make them available globally for the test.
        window.showBracketView(1, data);
    }""", mock_data)

    # Give the alignBracketConnectors function (called via setTimeout) time to run
    page.wait_for_timeout(500)

    # Take a screenshot of the entire bracket view
    bracket_view = page.locator("#bracket-view")
    expect(bracket_view).to_be_visible()
    
    # Set a larger viewport to capture the whole bracket if possible
    page.set_viewport_size({"width": 1920, "height": 1200})
    
    bracket_view.screenshot(path="jules-scratch/verification/final_layout.png")

    print("Screenshot saved to jules-scratch/verification/final_layout.png")

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
