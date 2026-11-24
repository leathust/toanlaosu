# ĐẶC TẢ TÍNH NĂNG & YÊU CẦU HỆ THỐNG — TOÁN LÃO SƯ (LARAVEL)

## 1. Tổng quan sản phẩm

- **Tên ứng dụng:** Toán Lão Sư, xây dựng trên Laravel 12 và PHP 8.2.
- **Mục tiêu:** Nền tảng dạy và học toán trực tuyến chuyên sâu cho **Toán THPT**, **HSA (ĐGNL ĐHQGHN)** và **TSA (ĐGNL ĐHBK Hà Nội)** theo hướng *learning by doing*.
- **Điểm khác biệt cốt lõi:** Học sinh không chỉ xem video, tài liệu hoặc lời giải tĩnh mà được thao tác trực tiếp với **mô hình trực quan 2D động và tương tác**, quan sát kết quả thay đổi và khám phá quy luật trước khi xem lời giải.
- **Phạm vi nội dung:** Bài học dạng chuỗi khối, video, tài liệu/slide, câu hỏi tương tác, mô hình 2D động, gia sư AI theo ngữ cảnh, thi online đồng thời nhiều người và xuất đề chuẩn LaTeX.

### 1.1. Nguyên tắc trải nghiệm học tập

- **Động:** Đối tượng có thể di chuyển, biến đổi hình dạng, đổi màu hoặc thay đổi trạng thái theo thời gian.
- **Tương tác:** Học sinh có thể kéo điểm, thay đổi tham số, chạy/tạm dừng/tua mô phỏng, mở hình khai triển hoặc đi từng bước.
- **Phản hồi toán học:** Hình vẽ, công thức và giải thích được cập nhật theo thao tác hoặc đáp án của học sinh.
- **Dẫn dắt thay vì đưa đáp án ngay:** Câu hỏi, mô hình và gia sư AI ưu tiên gợi mở từng bước.
- **Mobile-first:** Bài học, bài thi và mô hình phải sử dụng được trên điện thoại; khi thiết bị yếu phải có chế độ giảm chuyển động hoặc hình minh họa thay thế.

### 1.2. Mô hình người dùng và phân quyền

- **1 Admin vận hành:** Toàn quyền quản lý website, tài khoản, phạm vi giáo viên, cấu hình hệ thống, mẫu LaTeX, bộ scene 2D, cấu hình AI và toàn bộ dữ liệu.
- **Nhiều tài khoản Giáo viên:** Làm việc theo khóa học/chuyên đề được phân công hoặc quyền sở hữu nội dung được cấp.
- **Học sinh:** Học nội dung được cấp quyền, làm câu hỏi, thao tác mô hình 2D, sử dụng gia sư AI ngoài ca thi, tham gia thi và theo dõi tiến độ.
- Việc chỉ có một Admin là quy tắc vận hành trong giao diện; hệ thống phải có quy trình khôi phục khẩn cấp để tránh mất quyền quản trị.

---

## 2. Phân hệ học tập tương tác và chuỗi khối nội dung

### 2.1. Cấu trúc chương trình

- Phân cấp thống nhất: **Lộ trình (`tracks`)** $\rightarrow$ **Khóa học (`courses`)** $\rightarrow$ **Chương/Chuyên đề (`units`)** $\rightarrow$ **Bài học (`lessons`)**.
- Gắn mục tiêu/kỳ thi `THPT`, `HSA`, `TSA`, độ khó, kỹ năng, dạng bài và thời lượng dự kiến.
- Cây kỹ năng chuẩn hóa là nền tảng cho tìm kiếm câu hỏi, thống kê năng lực và đề xuất ôn tập.

### 2.2. Bài học dạng chuỗi khối (`lesson_blocks`)

Mỗi bài học là một chuỗi khối sắp xếp theo `sort_order`, không chia thành các tab nội dung tách rời:

1. `video`: Video bài giảng.
2. `material`: PDF, slide hoặc phiếu bài tập.
3. `note`: Ghi chú, công thức trọng tâm hoặc nhận xét toán học.
4. `question`: Câu hỏi dẫn dắt, vận dụng hoặc từng bước.
5. `visual`: Mô hình 2D động và tương tác.

**Cơ chế điều hướng và mở khóa:**

- `is_blocking` quy định khối phải hoàn thành trước khi mở khối kế tiếp; khối luyện thêm có thể mở tự do.
- Lưu trạng thái theo học sinh: `locked`, `unlocked`, `in_progress`, `completed`, `skipped`.
- Lưu số lần thử, đáp án, đúng/sai, thời gian làm, gợi ý đã xem và thời điểm hoàn thành.
- Có cơ chế “thoát hiểm”: mở gợi ý hoặc lời giải khi bị kẹt, nhưng phải ghi nhận để đưa vào danh sách ôn tập.
- Học sinh có thể tiếp tục bài học trên thiết bị khác từ khối và trạng thái gần nhất.

### 2.3. Các dạng câu hỏi

- Trắc nghiệm một đáp án.
- Trắc nghiệm nhiều đáp án.
- Điền số/giá trị, hỗ trợ số thập phân, phân số tương đương, sai số tuyệt đối/tương đối và đơn vị nếu câu hỏi yêu cầu.
- Câu hỏi gợi mở từng bước (`question_steps`), gồm lựa chọn phép biến đổi, nhập giá trị trung gian và gợi ý theo từng nấc.
- Video checkpoint tại `timestamp_sec`, cho phép học sinh thử giải trước khi xem tiếp.
- Phản hồi tức thì theo lỗi tư duy phổ biến: thiếu điều kiện, nhầm dấu, đọc sai đồ thị hoặc dùng sai công thức.

### 2.4. Quy tắc đáp án và chấm điểm

- Mỗi câu hỏi phải xác định rõ cách chấm: đúng toàn bộ, điểm từng phần hoặc có/không có điểm âm.
- Giá trị số phải khai báo quy tắc chuẩn hóa: dấu phẩy/dấu chấm thập phân, phân số tương đương, số chữ số làm tròn, sai số và đơn vị.
- Việc xem gợi ý/lời giải phải có quy tắc ảnh hưởng đến điểm, trạng thái hoàn thành và danh sách ôn tập.
- Thứ tự đáp án có thể được xáo trộn nhưng phải giữ ánh xạ chính xác tới đáp án gốc.
- Câu hỏi dùng trong kỳ thi phải tham chiếu phiên bản bất biến, không bị thay đổi khi giáo viên chỉnh sửa ngân hàng câu hỏi.

### 2.5. Vòng đời và phiên bản nội dung

- Nội dung có trạng thái: `draft` $\rightarrow$ `in_review` $\rightarrow$ `published` $\rightarrow$ `archived`.
- Lưu phiên bản câu hỏi, bài học và cấu hình mô hình quan trọng; hỗ trợ xem lịch sử và khôi phục phiên bản trước.
- Không chỉnh sửa trực tiếp phiên bản đang được dùng bởi ca thi đang diễn ra.
- Hỗ trợ soft delete và khôi phục đối với nội dung quan trọng.
- Phát hiện xung đột khi hai giáo viên cùng chỉnh sửa một nội dung.

---

## 3. Video bài giảng và tài liệu

### 3.1. Video bài giảng

- Lưu trên Object Storage hoặc dịch vụ video chuyên dụng; không lưu file nặng trên web server chính.
- Trạng thái xử lý: `uploading` $\rightarrow$ `processing` $\rightarrow$ `ready` / `failed`.
- Queue và webhook xử lý phải có retry, timeout, kiểm tra chữ ký và chống xử lý trùng.
- Theo dõi `last_position_sec`, các khoảng thời gian thực sự đã xem, tỷ lệ hoàn thành và `completed_at`; không chỉ cộng dồn heartbeat một cách máy móc.
- Cấp URL/token phát ngắn hạn sau khi kiểm tra đăng nhập và quyền khóa học.
- Watermark động là biện pháp răn đe, không được mô tả như cơ chế ngăn sao chép tuyệt đối.

### 3.2. PDF, slide và phiếu bài tập

- Hỗ trợ PDF để giữ nguyên font, công thức và hình vẽ; có thể đính kèm file nguồn nếu được phép.
- Xem trực tiếp bằng inline viewer và cấu hình `allow_download`.
- “Không cho tải” được hiểu là hạn chế nút tải và truy cập trực tiếp, không cam kết ngăn sao chép tuyệt đối trên trình duyệt.
- Lưu file ngoài thư mục public; truy cập qua route/controller có Policy và URL tạm thời.
- Kiểm tra MIME, phần mở rộng, dung lượng và tên file an toàn khi upload.
- Có thể ghi log lượt xem/tải (`material_views`) và gắn watermark cho tài liệu quan trọng.

---

## 4. Hệ thống trực quan toán học 2D động và tương tác

### 4.1. Định nghĩa và phạm vi

Hệ thống biểu diễn đối tượng toán học trên mặt phẳng bằng SVG, Canvas hoặc thư viện đồ thị. Vị trí, hình dạng, màu sắc, trạng thái và tham số của đối tượng có thể thay đổi:

- Theo thời gian của animation.
- Theo từng bước của lời giải.
- Theo thanh trượt hoặc thao tác kéo/thả của học sinh.
- Theo đáp án và điều kiện toán học đang được thỏa mãn.

Nội dung hình học không gian vẫn được hỗ trợ bằng **hình chiếu, mặt cắt, hình khai triển, nét khuất, lớp độ sâu và animation 2D**. Hệ thống không cần trình dựng không gian thời gian thực.

### 4.2. Các ví dụ trọng tâm

#### Máy bay và vùng kiểm soát radar

- Máy bay được biểu diễn bằng chấm đỏ chuyển động theo đường thẳng $P(t)=P_0+t\vec{v}$.
- Dùng mặt phẳng chứa tâm radar và đường bay; vùng kiểm soát hình cầu trở thành đường tròn trên mặt cắt này nên vẫn phản ánh chính xác bài toán.
- Khi $\lVert P(t)-C\rVert \leq R$, chấm máy bay hoặc vùng radar đổi màu và hiện trạng thái “Đã phát hiện”.
- Đánh dấu điểm vào/ra, vẽ đoạn khoảng cách tới tâm, hiển thị $d(t)$ và hỗ trợ chạy, tạm dừng, tua hoặc kéo thanh thời gian.

#### Con kiến bò trên chiếc hộp

- Chấm đại diện con kiến chuyển động qua các mặt hộp trong hình chiếu 2D.
- Animation mở các mặt thành hình khai triển.
- Đường đi trên các mặt trở thành đoạn hoặc đường gấp khúc trên hình khai triển để so sánh và tìm đường ngắn nhất.

#### Xây nhà và các khối trong không gian

- Dùng hình chiếu trục đo/phối cảnh 2D, nét liền/nét khuất và tô màu các mặt.
- Tường, mái, cột hoặc mặt cắt xuất hiện theo trình tự; kích thước thay đổi theo tham số.
- Tô sáng tam giác, đoạn vuông góc hoặc mặt cắt đang được dùng trong phép tính.

#### Diện tích mặt cong và khối tròn xoay

- Hiển thị đường sinh và animation quay tượng trưng trên mặt phẳng.
- Chia mặt thành các dải/lát nhỏ, tăng dần số lát và đồng bộ với tổng xấp xỉ hoặc tích phân.
- Khi phù hợp, animation trải các dải về mặt phẳng để giải thích công thức diện tích.

### 4.3. Phạm vi áp dụng theo chuyên đề

- **Hàm số:** Đồ thị thay đổi theo tham số, tiếp tuyến trượt, giao điểm/cực trị/tiệm cận và bảng biến thiên đồng bộ.
- **Đạo hàm:** Điểm chạy trên đồ thị, hệ số góc và tiếp tuyến cập nhật tức thì.
- **Nguyên hàm – Tích phân:** Quét diện tích, hình chữ nhật/hình thang xấp xỉ, thanh trượt $n$ và lát cắt.
- **Hình học phẳng và không gian:** Kéo điểm, hình chiếu, mặt cắt, hình khai triển, khoảng cách và góc.
- **Xác suất – Thống kê:** Mô phỏng phép thử, cây xác suất, biểu đồ và tần suất thực nghiệm hội tụ.

### 4.4. Hợp đồng kỹ thuật của scene 2D

- `engine`: `svg`, `2d_canvas`, `2d_graph` hoặc `chart`.
- `scene_key`: Tên mẫu scene được hệ thống cho phép.
- `schema_version`: Phiên bản cấu trúc cấu hình để có thể nâng cấp an toàn.
- `config_json`: Tham số đã được kiểm tra theo schema của từng `scene_key`.
- `steps`: Mốc đồng bộ giữa animation, trạng thái scene và các bước giải.
- `fallback`: Ảnh tĩnh hoặc lời giải chữ khi thiết bị không hỗ trợ đầy đủ hoặc người dùng bật chế độ giảm chuyển động.

Chỉ lazy-load thư viện cần thiết; phải dừng animation và giải phóng listener/tài nguyên khi đổi câu hoặc rời trang. Trang quản trị cung cấp form tham số và Live Preview. Không bắt buộc mọi câu hỏi đều có animation.

---

## 5. Thi online đồng thời nhiều người

### 5.1. Vòng đời ca thi và bài làm

- Ca thi: `draft` $\rightarrow$ `scheduled` $\rightarrow$ `open` $\rightarrow$ `closed` $\rightarrow$ `grading` $\rightarrow$ `published`; có thể `cancelled` khi có sự cố.
- Bài làm: `not_started` $\rightarrow$ `in_progress` $\rightarrow$ `submitted` / `expired` $\rightarrow$ `graded`.
- Cấu hình `starts_at`, `ends_at`, `duration_minutes`, thời gian được vào muộn và `grace_period_seconds` nếu có.
- Tạo snapshot/phiên bản đề, câu hỏi, đáp án, điểm và thứ tự xáo trộn cho từng thí sinh.

### 5.2. Đồng hồ, autosave và chịu lỗi

- Thời gian còn lại do server xác định từ `started_at`, thời lượng và chính sách ca thi; đồng hồ phía client chỉ để hiển thị.
- Lưu từng đáp án vào `exam_attempt_answers`; mỗi cặp bài làm–câu hỏi chỉ có một bản ghi hiện hành cùng phiên bản cập nhật.
- Autosave theo thao tác và định kỳ; giao diện phải báo trạng thái đang lưu, đã lưu hoặc mất kết nối.
- Refresh hoặc kết nối lại không làm mất đáp án và không kéo dài thời gian.
- Nộp bài phải idempotent; hai request đồng thời không được tạo hai kết quả hoặc chấm hai lần.
- Khi hết giờ, server khóa bài; câu trả lời đến sau hạn bị từ chối trừ khi Admin áp dụng chính sách xử lý sự cố.
- Cache đề đang diễn ra, rate limit hợp lý và dùng khóa nguyên tử/giao dịch cho các điểm tranh chấp.

### 5.3. Chấm điểm, kết quả và chống gian lận hợp lý

- Chấm tự động trắc nghiệm/điền số; thống kê chi tiết và bảng xếp hạng chạy qua Queue, có retry và chống chạy trùng.
- Ghi nhận chuyển tab, mất mạng và thay đổi thiết bị như tín hiệu để xem xét, không tự động kết luận gian lận.
- Cấu hình thời điểm công bố điểm, đáp án và lời giải.
- Gia sư AI và gợi ý học tập bị vô hiệu hóa trong ca thi; chatbot chỉ được hỗ trợ kỹ thuật nếu có chế độ riêng.
- Có quy trình hủy ca, gia hạn do sự cố và ghi audit log cho mọi can thiệp của Admin.

---

## 6. Xuất đề thi LaTeX

- Quản lý `latex_templates`: `preamble`, `header`, `question_format`, `solution_format`, `footer` và phiên bản mẫu.
- Chế độ xuất: không đáp án, có đáp án hoặc kèm lời giải chi tiết.
- Xuất theo thứ tự gốc hoặc snapshot đã xáo trộn.
- Escape dữ liệu văn bản đúng ngữ cảnh nhưng giữ nguyên công thức LaTeX đã được kiểm soát.
- Đóng gói `.zip` gồm `main.tex` và tài nguyên để biên dịch trên Overleaf/TeXstudio.
- MVP không biên dịch LaTeX tùy ý trên server. Nếu bổ sung sau này, tiến trình phải chạy trong sandbox, tắt shell escape, giới hạn CPU/RAM/thời gian và chỉ dùng gói được cho phép.

---

## 7. Gia sư AI theo ngữ cảnh bài học và mô hình 2D

### 7.1. Trải nghiệm cho học sinh

- Chatbot biết khóa học, bài học, câu hỏi, bước giải, đáp án vừa nhập và trạng thái scene 2D hiện tại trong phạm vi được phép.
- Ưu tiên hỏi ngược và gợi ý Socratic theo nhiều cấp độ trước khi đưa lời giải hoàn chỉnh.
- Chỉ ra lỗi dựa trên bài làm của học sinh và có thể tạo một câu tương tự để luyện lại.
- Trả lời dựa trên nội dung đã xuất bản, kèm nguồn tới bài học/tài liệu liên quan; phải thừa nhận khi không đủ dữ liệu.
- Streaming phản hồi để giảm cảm giác chờ, nhưng phải có timeout, thông báo lỗi và khả năng thử lại an toàn.
- Học sinh có thể đánh giá phản hồi hoặc báo câu trả lời sai để giáo viên/Admin kiểm tra.

### 7.2. Điều khiển mô hình 2D có kiểm soát

Chatbot có thể giải thích đồng thời với việc gửi các lệnh cấu trúc tới scene hiện tại, ví dụ:

- `play`, `pause`, `seek`, `reset`.
- `set_parameter` với tên và khoảng giá trị được scene cho phép.
- `highlight` đối tượng đã khai báo.
- `show_segment`, `show_label` hoặc chuyển tới một `step` hợp lệ.

Mọi lệnh phải được kiểm tra theo allow-list và schema của scene. AI không được sinh rồi thực thi JavaScript, HTML hoặc mã tùy ý trên trình duyệt/server.

Ví dụ khi học sinh hỏi vì sao máy bay bị radar phát hiện, chatbot có thể dừng tại thời điểm vào vùng, tô sáng tâm và máy bay, vẽ đoạn khoảng cách rồi hiển thị $\lVert P(t)-C\rVert=R$.

### 7.3. Kiến trúc, an toàn và chi phí

- Tích hợp nhà cung cấp AI qua một contract riêng để có thể thay model/nhà cung cấp và dùng fake trong test.
- Chỉ gửi dữ liệu tối thiểu cần thiết; không gửi bí mật hệ thống hoặc toàn bộ lịch sử học tập khi không cần.
- Nội dung truy xuất phải bị giới hạn theo enrollment, trạng thái xuất bản và quyền người dùng.
- Chống prompt injection từ nội dung/tài liệu; output phải được escape khi hiển thị và lệnh công cụ phải được xác thực độc lập.
- Rate limit theo người dùng/gói học; lưu token/chi phí/độ trễ/lỗi để theo dõi ngân sách.
- HTTP client phải có connect timeout, response timeout, retry có kiểm soát và xử lý rõ lỗi từ nhà cung cấp.
- Lưu hội thoại theo chính sách thời hạn; cho phép ẩn/xóa dữ liệu theo quy định vận hành.
- Chatbot không tự xuất bản câu hỏi, lời giải hoặc nội dung do AI tạo; giáo viên phải duyệt.

### 7.4. Công cụ hỗ trợ giáo viên (giai đoạn sau)

- Gợi ý câu hỏi tương tự, distractor, lời giải từng bước hoặc tag kỹ năng.
- Tóm tắt các lỗi phổ biến từ dữ liệu đã ẩn danh phù hợp.
- Mọi nội dung AI tạo ở trạng thái nháp và có thông tin người duyệt trước khi xuất bản.

---

## 8. Khu vực quản trị

### 8.1. Admin

- Quản lý tài khoản, phân quyền, phạm vi giáo viên, khóa học, quyền truy cập, cấu hình lưu trữ, Queue, scene 2D, mẫu LaTeX và AI.
- Gán giáo viên theo khóa học/chuyên đề; khóa tài khoản và thu hồi phiên đăng nhập khi cần.
- Quản lý workflow duyệt nội dung, audit log, sử dụng AI, lỗi webhook/Queue và ca thi đang hoạt động.

### 8.2. Giáo viên

- Quản lý nội dung trong phạm vi được giao; không đọc/sửa/xóa dữ liệu ngoài phạm vi.
- Upload video/tài liệu, soạn câu hỏi và bước giải, cấu hình scene 2D từ mẫu, tạo đề/ca thi và xuất LaTeX.
- Xem tiến độ, lỗi phổ biến và phản hồi chatbot của học sinh trong phạm vi được phép.

### 8.3. Công cụ soạn thảo

- Live Preview công thức bằng KaTeX/MathJax và Live Preview scene 2D động.
- Form cấu hình theo schema, không cho nhập mã thực thi tùy ý.
- Lưu `created_by`, `updated_by`, phiên bản và audit log thao tác quan trọng.
- Kiểm soát quyền bằng Role/Permission, Laravel Policy và phạm vi khóa học/chuyên đề.

---

## 9. Tính năng bổ sung

### 9.1. Cho học sinh

- Danh sách ôn lại câu sai, câu làm chậm hoặc câu đã phải mở gợi ý.
- Chẩn đoán điểm yếu theo kỹ năng/dạng bài và đề xuất bài luyện phù hợp.
- Lịch thi, đếm ngược và thông báo kết quả.
- Hỏi đáp với giáo viên dưới bài học/câu hỏi; ghi chú cá nhân.
- Chuỗi ngày học và thống kê tiến độ.

### 9.2. Cho vận hành

- Quyền truy cập khóa học có thời hạn, cấp thủ công hoặc theo gói học.
- Tìm kiếm, lọc, tái sử dụng và cảnh báo câu hỏi trùng lặp.
- Import câu hỏi theo lô từ `.tex`, JSON hoặc Excel, có preview, validation và báo lỗi từng dòng.
- Quản lý đăng nhập nhiều thiết bị và thu hồi session.
- Thanh toán tự động khi thương mại hóa.
- Dashboard sức khỏe hệ thống: Queue lỗi, webhook lỗi, video xử lý thất bại, chi phí AI và ca thi đang diễn ra.

---

## 10. Kiến trúc và mô hình dữ liệu gợi ý

### 10.1. Tech stack

- **Backend:** Laravel 12, PHP 8.2, MySQL; Redis cho cache, rate limit, khóa nguyên tử và Queue khi triển khai production.
- **Frontend tương tác:** Chốt một hướng trước khi triển khai diện rộng; Inertia + Vue phù hợp cho trình học, bài thi và scene 2D có nhiều trạng thái. Khu vực quản trị có thể dùng Filament hoặc giao diện tùy biến sau khi prototype.
- **Toán và trực quan:** KaTeX/MathJax; SVG, Canvas, JSXGraph và Chart.js tùy loại scene. Không đưa thư viện nặng vào trang khi scene không cần.
- **Tệp:** Object Storage/dịch vụ video với URL tạm thời.
- **AI:** Tích hợp qua contract nội bộ, không để domain phụ thuộc trực tiếp vào SDK của một nhà cung cấp.

### 10.2. Nhóm bảng dữ liệu cốt lõi

- **Người dùng và quyền:** `users`, `roles`, `permissions`, `course_teachers`, `enrollments`, `access_grants`.
- **Nội dung:** `tracks`, `courses`, `units`, `lessons`, `lesson_blocks`, `content_revisions`, `lesson_block_progress`.
- **Video/tài liệu:** `lesson_videos`, `video_progress`, `lesson_materials`, `material_views`.
- **Câu hỏi/kỹ năng:** `questions`, `question_versions`, `question_options`, `question_steps`, `skills`, `question_skill`, `user_attempts`, `user_mastery`.
- **Trực quan:** `visual_scenes`, `visual_scene_versions`, `question_visuals`.
- **Thi cử:** `exams`, `exam_versions`, `exam_questions`, `exam_attempts`, `exam_attempt_answers`, `exam_events`.
- **LaTeX:** `latex_templates`, `latex_template_versions`, `export_jobs`.
- **AI:** `chat_threads`, `chat_messages`, `chat_message_sources`, `ai_usage_logs`, `ai_feedback`.
- **Vận hành:** `audit_logs`, `notifications` và các bảng Queue/cache chuẩn của Laravel.

Các chỉ mục và ràng buộc duy nhất phải xuất phát từ truy vấn thực tế, đặc biệt cho tiến độ học, autosave đáp án, phân công giáo viên, ca thi và lịch sử chat. Dữ liệu lớn phải được phân trang/chunk và tránh N+1 khi tải bài học hoặc báo cáo.

---

## 11. Yêu cầu phi chức năng, bảo mật và vận hành

- **Phân quyền:** Mọi thao tác nội dung/tài liệu/ca thi/chat phải kiểm tra Policy và phạm vi; không chỉ dựa vào việc đã đăng nhập.
- **Upload:** Giới hạn loại, MIME, phần mở rộng và dung lượng; lưu ngoài vùng public có thể thực thi.
- **Rate limit:** Áp dụng cho đăng nhập, upload, autosave, chatbot, export và endpoint tốn tài nguyên; khóa theo người dùng kết hợp IP khi phù hợp.
- **Tính nhất quán:** Submit, webhook, grading và job tạo export phải idempotent; dùng transaction/atomic lock tại điểm có race condition.
- **Quan sát:** Log có context, theo dõi Queue thất bại, webhook, độ trễ autosave, lỗi AI và tỷ lệ video xử lý thất bại; không ghi secret hoặc dữ liệu nhạy cảm không cần thiết.
- **Backup và phục hồi:** Có lịch backup database/tệp, kiểm tra restore định kỳ và quy trình khôi phục Admin.
- **Thời gian:** Lưu timestamp theo UTC; hiển thị theo múi giờ cấu hình, mặc định `Asia/Ho_Chi_Minh`.
- **Hiệu năng:** Trước khi mở ca thi thật phải chốt số thí sinh đồng thời, chu kỳ autosave, mục tiêu độ trễ p95, dung lượng upload và chạy load test tương ứng.
- **Khả năng tiếp cận:** Điều khiển được bằng bàn phím khi hợp lý, có mô tả chữ cho hình quan trọng, không dùng màu sắc làm tín hiệu duy nhất và hỗ trợ giảm chuyển động.
- **Dependency:** Không thêm package hoặc đổi hạ tầng nếu chưa đánh giá nhu cầu, bảo trì, giấy phép và rủi ro bảo mật.

---

## 12. Tiêu chí nghiệm thu tối thiểu

- Học sinh reload hoặc kết nối lại vẫn còn đáp án đã lưu; đồng hồ thi không tăng thêm.
- Hai request nộp bài đồng thời chỉ tạo một kết quả và chỉ chấm một lần.
- Sửa câu hỏi sau khi ca thi bắt đầu không thay đổi đề/đáp án snapshot của thí sinh.
- Giáo viên không thể xem hoặc sửa nội dung ngoài phạm vi được giao.
- URL tài liệu hết hạn hoặc người dùng hết enrollment không còn truy cập được.
- Webhook video gửi lặp không tạo trạng thái/job trùng.
- Scene radar di chuyển chấm máy bay theo $t$, đổi trạng thái đúng tại biên và hoạt động với play/pause/seek.
- Scene hình hộp có thể animation sang hình khai triển và giữ đúng các cạnh tương ứng.
- Cấu hình scene sai schema hoặc lệnh AI ngoài allow-list bị từ chối, không thực thi mã tùy ý.
- Chatbot chỉ dùng nguồn người học được phép truy cập, có thể nêu nguồn và không hoạt động như gia sư trong ca thi.
- Khi nhà cung cấp AI timeout/lỗi, giao diện báo lỗi rõ ràng, không mất tin nhắn người dùng và không tạo phản hồi trùng khi thử lại.
- Nội dung do AI gợi ý không thể tự chuyển sang `published` nếu chưa có giáo viên duyệt.

---

## 13. Lộ trình triển khai

### Giai đoạn 0 — Nền tảng

- Xác thực, phân quyền, Policy/phạm vi giáo viên.
- Cấu trúc lộ trình–khóa học–chuyên đề–bài học, workflow xuất bản và versioning cơ bản.
- Ngân hàng câu hỏi, kỹ năng, quy tắc chấm và quyền truy cập khóa học.

### MVP 1 — Học tập tương tác

- Chuỗi lesson block gồm note, PDF, video, câu hỏi và tiến độ.
- Ba dạng câu hỏi chính: một đáp án, nhiều đáp án, điền số.
- Một số scene 2D động trọng tâm: đồ thị/tham số, radar–máy bay, hình khai triển hộp và xấp xỉ tích phân.
- Trải nghiệm mobile và fallback giảm chuyển động.

### MVP 2 — Thi cử và LaTeX

- Ca thi, snapshot đề, đồng hồ server, autosave, kết nối lại, submit idempotent và chấm điểm.
- Xuất `.tex/.zip` theo template; chưa biên dịch tùy ý trên server.
- Dashboard vận hành ca thi và Queue cơ bản.

### MVP 3 — Gia sư AI

- Chat theo bài học/câu hỏi, gợi ý Socratic, nguồn tham chiếu, streaming và giới hạn sử dụng.
- Phân tích đáp án vừa làm và điều khiển scene bằng bộ lệnh an toàn.
- Vô hiệu hóa trong ca thi và có cơ chế báo phản hồi sai.

### V2

- Ôn tập thích nghi và thống kê năng lực sâu hơn.
- Công cụ AI hỗ trợ giáo viên có bước duyệt.
- Hỏi đáp giáo viên, import hàng loạt, streak/thông báo, quản lý đa thiết bị và thanh toán.

### Ngoài phạm vi phiên bản đầu

- Trình dựng không gian thời gian thực hoặc cho phép người dùng tự nạp mã scene.
- Biên dịch LaTeX tùy ý trên web server.
- AI tự xuất bản nội dung hoặc được sử dụng để giải bài trong ca thi.
