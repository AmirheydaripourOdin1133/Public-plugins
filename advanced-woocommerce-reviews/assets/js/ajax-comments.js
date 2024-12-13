var $ = jQuery;

// Jq

document.addEventListener("DOMContentLoaded", function () {
  // مدیریت کلیک روی دکمه‌های پاسخ
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("reply-btn")) {
      const commentId = e.target.dataset.commentId;
      const isLoggedIn = ajaxComments.isLoggedIn; // وضعیت لاگین

      const repliesContainer = document.querySelector(
        `#comment-${commentId} .replies`
      );

      const existingForm = repliesContainer.querySelector(".ajax-reply-form");

      if (existingForm) {
        // اگر فرم موجود بود، آن را بسته و از بین ببریم
        repliesContainer.innerHTML = "";
        repliesContainer.classList.remove("open");
      } else {
        const replyFormHtml = `
        
          <form class="ajax-reply-form" data-comment-id="${commentId}">
                    <span class="close-reply-form"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m15.958 8.042-7.916 7.916m7.916 0L8.042 8.042M12 21.5a9.5 9.5 0 1 0 0-19 9.5 9.5 0 0 0 0 19" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"></path></svg> لغـو پاسـخ </span>

            ${
              isLoggedIn
                ? `<input type="hidden" name="author" value="${ajaxComments.userName}">`
                : `<input type="text" name="author" placeholder="نام شما" required>`
            }
            <textarea name="comment" placeholder="متن پاسخ" required></textarea>
            <button type="submit" class="GenralBtns">ارسال پاسخ</button>
          </form>
        `;
        repliesContainer.innerHTML = replyFormHtml;

        const replyForm = repliesContainer.querySelector(".ajax-reply-form");
        replyForm.addEventListener("submit", submitReply);

        const closeReplyForm =
          repliesContainer.querySelector(".close-reply-form");
        closeReplyForm.addEventListener("click", function () {
          repliesContainer.innerHTML = "";
          repliesContainer.classList.remove("open");
        });

        repliesContainer.classList.add("open");
      }
    }
  });

  // ارسال پاسخ با AJAX
  function submitReply(e) {
    e.preventDefault();
    const form = e.target;
    const commentId = form.dataset.commentId;
    const repliesContainer = form.closest(".replies");
    const overlay = document.querySelector(".GeneralOverlay");

    const formData = new FormData(form);
    formData.append("action", "submit_reply");
    formData.append("parent_id", commentId);
    formData.append("post_id", ajaxComments.postId);

    fetch(ajaxComments.ajaxUrl, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // show success text
          showPopupMessage(
            "reply-popup",
            "پاسخ شما ثبت شد و پس از تأیید نمایش داده خواهد شد."
          );

          setTimeout(() => {
            // this hide form
            repliesContainer.innerHTML = "";
            repliesContainer.classList.remove("open");
            overlay.classList.remove("open");
            overlay.style.display = "none";
          }, 1000);

          loadComments();
        } else {
          showPopupMessage(
            "reply-popup",
            "خطا در ارسال پاسخ. لطفاً دوباره تلاش کنید."
          );
        }
      });
  }

  function showPopupMessage(popupId, message) {
    const popup = document.getElementById(popupId);
    if (popup) {
      popup.textContent = message;
      popup.style.display = "block";

      setTimeout(() => {
        popup.style.display = "none";
      }, 8000); // 2 ثانیه
    }
  }

  const commentForm = document.getElementById("ajax-comment-form");
  if (commentForm) {
    commentForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      const overlay = document.querySelector(".GeneralOverlay");
      const ParentPupComents = document.querySelector(".commentCustomsPopup");

      fetch(ajaxComments.ajaxUrl, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // نمایش پیام موفقیت برای 2 ثانیه
            showPopupMessage(
              "reply-popup",
              "پاسخ شما ثبت شد و پس از تأیید نمایش داده خواهد شد."
            );

            setTimeout(() => {
              overlay.classList.remove("open");
              ParentPupComents.classList.remove("open");
              overlay.style.display = "none";
            }, 900); // 2 ثانیه تأخیر
          } else {
            showPopupMessage(
              "reply-popup",
              "خطا در ارسال پاسخ. لطفاً دوباره تلاش کنید."
            );
          }
        });
    });
  }
});

document
  .getElementById("open-comment-popup")
  .addEventListener("click", function () {
    const commentPopup = document.getElementById("comment-popup");
    const overlay = document.querySelector(".GeneralOverlay");

    if (commentPopup.classList.contains("open")) {
      commentPopup.classList.remove("open");
      overlay.style.display = "none";
    } else {
      commentPopup.classList.add("open");
      overlay.style.display = "block";
    }
  });

document
  .querySelector(".closs_custom_comment_Gen")
  .addEventListener("click", function () {
    const commentPopup = document.getElementById("comment-popup");
    const overlay = document.querySelector(".GeneralOverlay");

    // حذف کلاس open از comment-popup و مخفی کردن overlay
    commentPopup.classList.remove("open");
    overlay.style.display = "none";
  });

// ثبت امتیاز با کلیک روی ستاره‌ها
document.querySelectorAll(".star-rating span").forEach((star) => {
  star.addEventListener("click", function () {
    document.getElementById("rating-input").value = this.dataset.value;
    document
      .querySelectorAll(".star-rating span")
      .forEach((s) => s.classList.remove("selected"));
    this.classList.add("selected");
  });
});
document.addEventListener("DOMContentLoaded", function () {
  const stars = document.querySelectorAll(".star-rating span");
  const tooltip = document.getElementById("tooltip");
  const tooltipRating = document.getElementById("tooltip-rating");
  const ratingInput = document.getElementById("rating-input");

  stars.forEach((star) => {
    star.addEventListener("click", function () {
      const ratingValue = parseInt(this.dataset.value);
      ratingInput.value = ratingValue;

      tooltipRating.textContent = ratingValue;
      tooltip.classList.add("show");
      setTimeout(() => {
        tooltip.classList.remove("show");
      }, 2000);
    });

    // رویداد هاور برای تغییر حالت ستاره‌ها
    star.addEventListener("mouseover", function () {
      const ratingValue = parseInt(this.dataset.value);
      stars.forEach((s, index) => {
        if (index < ratingValue) {
          s.classList.add("hover");
        } else {
          s.classList.remove("hover");
        }
      });
    });

    // حذف حالت هاور وقتی موس خارج می‌شود
    star.addEventListener("mouseout", function () {
      stars.forEach((s) => s.classList.remove("hover"));
    });
  });
});

// تابع نمایش پیام پاپ آپ
function showPopupMessage(popupId, message) {
  const popup = document.getElementById(popupId);
  if (popup) {
    popup.textContent = message; // نمایش پیام در پاپ آپ
    popup.style.display = "block"; // نمایش پاپ آپ

    // بعد از 3 ثانیه پاپ آپ را مخفی کنیم
    setTimeout(() => {
      popup.style.display = "none"; // مخفی کردن پاپ آپ بعد از 3 ثانیه
    }, 3000); // 3 ثانیه تأخیر
  }
}

// مرتب‌سازی کامنت‌ها با AJAX
document.querySelectorAll(".sort-btn").forEach((button) => {
  button.addEventListener("click", function () {
    const sortType = this.getAttribute("data-sort");
    loadComments(sortType);
  });
});

// بارگذاری اولیه کامنت‌ها
loadComments();

// هندلینگ صفحه‌بندی
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("pagination-link")) {
    e.preventDefault();
    const page = e.target.getAttribute("data-page");
    loadComments("newest", page); // مرتب‌سازی به دلخواه تغییر کند
  }
});

$(document).ready(function () {
  let selectedRating;
  $(".custom-star").on("mouseover", function () {
    let rating = $(this).data("value");
    resetStars();
    highlightStars(rating);
  });

  $(".custom-star").on("mouseout", function () {
    resetStars();
    if (selectedRating > 0) {
      highlightStars(selectedRating);
    }
  });

  $(".custom-star").on("click", function () {
    selectedRating = $(this).data("value");
    resetStars();
    highlightStars(selectedRating);
  });

  function highlightStars(rating) {
    $(".custom-star").each(function () {
      if ($(this).data("value") <= rating) {
        $(this).addClass("selected");
      }
    });
  }

  function resetStars() {
    $(".custom-star").removeClass("selected");
  }
});

document.addEventListener("DOMContentLoaded", function () {
  // انتخاب تمام دکمه‌های .sort-btn داخل .SortCommentLists
  const sortContainer = document.querySelector(".SortCommentLists");

  if (sortContainer) {
    sortContainer.addEventListener("click", function (event) {
      // بررسی اینکه آیا روی یک دکمه با کلاس .sort-btn کلیک شده است
      const clickedButton = event.target.closest(".sort-btn");

      if (clickedButton) {
        // حذف کلاس selctsort از همه دکمه‌های دیگر
        const previousSelected = sortContainer.querySelector(
          ".sort-btn.selctsort"
        );
        if (previousSelected) {
          previousSelected.classList.remove("selctsort");
        }

        // اضافه کردن کلاس selctsort به دکمه کلیک‌شده
        clickedButton.classList.add("selctsort");
      }
    });
  }
});
