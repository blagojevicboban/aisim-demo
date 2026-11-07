# AISim Demo Site (AI for VET)

Public interactive demo repository for **AI for VET** Moodle plugins:
- [filter_aisim](https://github.com/blagojevicboban/moodle-filter_aisim) — 9 client-side AI/ML interactive simulations
- [qtype_aisim](https://github.com/blagojevicboban/moodle-qtype_aisim) — Auto-graded interactive simulation challenges for Moodle Quizzes
- [tiny_aisim](https://github.com/blagojevicboban/moodle-tiny_aisim) — Visual TinyMCE simulation inserter and editor

Project portal: [https://aiforvet.eu/](https://aiforvet.eu/)  
Standalone simulations: [https://sim.aiforvet.eu/](https://sim.aiforvet.eu/)  
Live demo site: [https://aisim.tsp.edu.rs/](https://aisim.tsp.edu.rs/)

---

## Courses Included

1. **`AISIM-DEMO`**: AI for VET: interactive simulations (English)
2. **`AISIM-DEMO-SR`**: AI za VET: interaktivne simulacije (srpski)
3. **`AISIM-DEMO-SK`**: AI pre OVP: interaktívne simulácie (slovenčina)
4. **`AISIM-DEMO-PT`**: IA para EFP: simulações interativas (português)

Each course contains:
- **9 Educational Lesson Pages** with embedded live simulations across 4 thematic areas:
  1. *Gradient Descent Optimization* (`gradient-descent`)
  2. *Confusion Matrix & Decision Threshold* (`confusion-matrix`)
  3. *Polynomial Regression & L2 Regularization* (`regression`)
  4. *k-NN vs Decision Trees* (`knn-tree`)
  5. *K-Means Clustering* (`kmeans`)
  6. *Perceptrons & Neural Networks* (`neural-network`)
  7. *Convolution & Image Feature Filters* (`convolution`)
  8. *Reinforcement Learning: Q-Learning* (`q-learning`)
  9. *Data Split & Fairness / Bias* (`data-bias`)
- **Auto-Graded Interactive Quiz** with 9 simulation challenges powered by `qtype_aisim`.
- **General Discussion Forum** for sharing hyperparameters and experiment findings.
- **Section 0 Walkthrough** banner with 4-step guided tour and language toggle.

---

## Demo Accounts

- **Guest:** Free access to explore all 9 simulations and frontpage interactive tools.
- **Student:** `student` / `Demo-2026` (interact with simulations, take quizzes, post in forums).
- **Teacher:** `teacher` / `Demo-2026` (edit pages, test TinyMCE simulation inserter, configure quizzes).

All user changes, posts, and quiz attempts reset every night at 03:30.

---

## Deployment & Automation

The repository is linked to `https://aisim.tsp.edu.rs/webhook` via PM2 (`aisim-webhook` on port `9012`).  
Any `git push origin main` triggers automatic pull, archive copy, and `php demo.php reset`.
