<!-- ==================================================
     FROSTCORE LOGOUT CONFIRMATION
================================================== -->

<style>

    .logout-overlay {
        position: fixed;
        inset: 0;

        display: none;
        align-items: center;
        justify-content: center;

        padding: 20px;

        background: rgba(0, 0, 0, 0.78);

        z-index: 99999;
    }


    .logout-overlay.show {
        display: flex;
    }


    .logout-modal {
        position: relative;

        width: 100%;
        max-width: 420px;

        padding: 35px 30px;

        background: #101820;

        border: 1px solid #4DBCF4;
        border-radius: 12px;

        text-align: center;

        box-shadow:
            0 0 35px rgba(77, 188, 244, 0.20);
    }


    .logout-close {
        position: absolute;

        top: 10px;
        right: 13px;

        width: 35px;
        height: 35px;

        border: none;
        background: transparent;

        color: #AAB5CA;

        font-size: 27px;
        line-height: 1;

        cursor: pointer;
    }


    .logout-close:hover {
        color: #4DBCF4;
    }


    .logout-icon {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 58px;
        height: 58px;

        margin: 0 auto 18px;

        border: 2px solid #4DBCF4;
        border-radius: 50%;

        color: #4DBCF4;

        font-size: 27px;
    }


    .logout-modal h2 {
        margin: 0 0 10px;

        color: #F4F7FF;

        font-family: Orbitron, sans-serif;

        font-size: 22px;

        letter-spacing: 1px;
    }


    .logout-modal p {
        margin: 0 auto 25px;

        max-width: 320px;

        color: #AAB5CA;

        font-size: 13px;

        line-height: 1.6;
    }


    .logout-actions {
        display: flex;

        gap: 12px;
    }


    .logout-cancel,
    .logout-confirm {
        flex: 1;

        min-height: 42px;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 10px 15px;

        border-radius: 6px;

        font-size: 10px;
        font-weight: 800;

        cursor: pointer;

        text-decoration: none;

        transition: 0.2s ease;
    }


    .logout-cancel {
        background: transparent;

        border: 1px solid #263452;

        color: #F4F7FF;
    }


    .logout-cancel:hover {
        border-color: #4DBCF4;

        color: #4DBCF4;
    }


    .logout-confirm {
        background: #4DBCF4;

        border: 1px solid #4DBCF4;

        color: #050A16;
    }


    .logout-confirm:hover {
        background: #F4F7FF;

        border-color: #F4F7FF;
    }


    @media (max-width: 500px) {

        .logout-modal {
            padding: 30px 20px;
        }


        .logout-actions {
            flex-direction: column;
        }

    }

</style>


<div
    class="logout-overlay"
    id="logoutOverlay"
    aria-hidden="true"
>

    <div
        class="logout-modal"
        role="dialog"
        aria-modal="true"
    >

        <button
            type="button"
            class="logout-close"
            id="logoutClose"
        >
            ×
        </button>


        <div class="logout-icon">
            ⏻
        </div>


        <h2>
            LOG OUT?
        </h2>


        <p>
            Are you sure you want to log out of your
            FROSTCORE account?
        </p>


        <div class="logout-actions">

            <button
                type="button"
                class="logout-cancel"
                id="logoutCancel"
            >
                CANCEL
            </button>


            <a
                href="logout.php"
                class="logout-confirm"
            >
                LOG OUT
            </a>

        </div>

    </div>

</div>